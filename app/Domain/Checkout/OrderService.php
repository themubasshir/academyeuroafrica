<?php

namespace App\Domain\Checkout;

use App\Models\AuditLog;
use App\Models\CourseVariant;
use App\Models\Enrolment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentEvent;
use App\Models\PaymentTransaction;
use App\Models\Refund;
use App\Models\User;
use App\Services\Payments\Contracts\PaymentGateway;
use App\Services\Payments\PaymentManager;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Order creation and payment fulfilment. Prices are always read from the
 * CourseVariant server-side — never from request input.
 */
class OrderService
{
    /** Create a pending order for the user from variant IDs. */
    public function create(User $user, array $variantIds): Order
    {
        abort_if(empty($variantIds), 422, 'No items selected.');

        return DB::transaction(function () use ($user, $variantIds) {
            $order = Order::create([
                'user_id' => $user->id,
                'reference' => 'EAMN-'.strtoupper(Str::random(10)),
                'status' => 'pending',
                'total_cents' => 0,
                'currency' => 'EUR',
            ]);

            $total = 0;
            foreach ($variantIds as $variantId) {
                $variant = CourseVariant::with('course')->findOrFail($variantId);
                abort_unless($variant->isPurchasable(), 422, 'Item not purchasable.');

                OrderItem::create([
                    'order_id' => $order->id,
                    'course_variant_id' => $variant->id,
                    'name' => $variant->course->title.' — '.$variant->name,
                    'unit_price_cents' => $variant->price_cents,
                    'quantity' => 1,
                ]);
                $total += $variant->price_cents;
            }

            $order->update(['total_cents' => $total]);
            AuditLog::record('order.created', $order, ['total' => $total]);

            return $order;
        });
    }

    /**
     * Record a gateway transaction for an order and return the checkout URL.
     *
     * @return array{order:Order, redirect_url:string}
     */
    public function beginPayment(Order $order, PaymentGateway $gateway): array
    {
        return DB::transaction(function () use ($order, $gateway): array {
            $order = Order::lockForUpdate()->findOrFail($order->id);
            abort_unless($order->status === 'pending', 422, 'Order is not payable.');

            $existing = PaymentTransaction::where('order_id', $order->id)
                ->where('gateway', $gateway->name())
                ->where('status', 'initiated')
                ->latest('id')->first();
            if ($existing && filled($existing->metadata['redirect_url'] ?? null)) {
                return ['order' => $order, 'redirect_url' => $existing->metadata['redirect_url']];
            }

            $checkout = $gateway->createCheckout($order);
            PaymentTransaction::updateOrCreate(
                ['gateway' => $gateway->name(), 'gateway_transaction_id' => $checkout['transaction_id']],
                [
                    'order_id' => $order->id,
                    'status' => 'initiated',
                    'amount_cents' => $order->total_cents,
                    'currency' => $order->currency,
                    'metadata' => ['redirect_url' => $checkout['redirect_url']],
                ]
            );

            return ['order' => $order, 'redirect_url' => $checkout['redirect_url']];
        });
    }

    /**
     * Idempotent webhook fulfilment. The unique (gateway, gateway_event_id)
     * index makes replays a no-op; enrolment unique index prevents duplicates.
     */
    public function fulfil(string $gatewayName, array $event): void
    {
        DB::transaction(function () use ($gatewayName, $event) {
            // Insert-first idempotency: duplicate event_id throws and exits early.
            try {
                PaymentEvent::create([
                    'gateway' => $gatewayName,
                    'gateway_event_id' => $event['event_id'],
                    'type' => $event['type'],
                    'payload' => $event['payload'],
                ]);
            } catch (UniqueConstraintViolationException) {
                return; // already processed
            }

            $paidTypes = [
                'checkout.session.completed', // stripe legacy
                'payment.completed',           // normalised capture
                'test.paid',                   // test adapter
                'PAYMENT.CAPTURE.COMPLETED',   // paypal webhook
            ];
            if (! in_array($event['type'], $paidTypes, true)) {
                PaymentEvent::where('gateway_event_id', $event['event_id'])->update(['processed_at' => now()]);

                return; // not a paid event
            }

            // Resolve the order: prefer the reference; fall back to the stored
            // gateway transaction id (paypal webhooks may omit custom_id).
            $order = null;
            if (! empty($event['order_reference'])) {
                $order = Order::where('reference', $event['order_reference'])->lockForUpdate()->first();
            }
            if ($order === null && ! empty($event['transaction_id'])) {
                $txn = PaymentTransaction::where('gateway_transaction_id', $event['transaction_id'])->first();
                $order = $txn ? Order::lockForUpdate()->find($txn->order_id) : null;
            }
            if ($order === null) {
                PaymentEvent::where('gateway_event_id', $event['event_id'])->update(['processed_at' => now()]);

                return; // unresolvable — recorded for reconciliation
            }
            if ($order->isPaid()) {
                PaymentEvent::where('gateway_event_id', $event['event_id'])->update(['processed_at' => now()]);

                return; // already fulfilled
            }

            $txn = PaymentTransaction::where('gateway_transaction_id', $event['transaction_id'])
                ->orWhere('order_id', $order->id)->latest()->first();
            $txn?->update([
                'status' => 'paid',
                'metadata' => array_merge($txn->metadata ?? [], [
                    'event_id' => $event['event_id'],
                    // capture id for refunds — for paypal event_id is the capture id
                    'capture_id' => $event['capture_id'] ?? $event['event_id'],
                ]),
            ]);

            $order->update(['status' => 'paid']);

            foreach ($order->items as $item) {
                Enrolment::firstOrCreate(
                    ['user_id' => $order->user_id, 'course_variant_id' => $item->course_variant_id],
                    ['order_id' => $order->id, 'status' => 'active', 'enrolled_at' => now()]
                );
            }

            PaymentEvent::where('gateway_event_id', $event['event_id'])->update(['processed_at' => now()]);
            AuditLog::record('order.paid', $order, ['gateway' => $gatewayName, 'event' => $event['event_id']]);
        });
    }

    /**
     * Refund a paid order and reconcile its purchased entitlements.
     *
     * Full refunds suspend every enrolment created by this order. Partial
     * refunds are recorded without changing access because a partial monetary
     * adjustment does not identify a course entitlement to remove.
     */
    public function refund(Order $order, User $admin, int $amountCents, ?string $reason = null): Refund
    {
        abort_unless($admin->isAdmin(), 403);
        abort_unless($order->isPaid(), 422, 'Order is not paid.');

        return DB::transaction(function () use ($order, $admin, $amountCents, $reason): Refund {
            $txn = PaymentTransaction::where('order_id', $order->id)
                ->where('status', 'paid')->lockForUpdate()->firstOrFail();

            $order->loadMissing('items');
            $alreadyRefunded = Refund::where('payment_transaction_id', $txn->id)
                ->where('status', 'completed')->sum('amount_cents');

            abort_if($amountCents <= 0, 422, 'Invalid refund amount.');
            abort_if($alreadyRefunded + $amountCents > $txn->amount_cents, 422, 'Refund exceeds paid amount.');

            $captureId = $txn->metadata['capture_id'] ?? $txn->gateway_transaction_id;
            $idempotencyKey = 'refund:'.$txn->id.':'.$amountCents.':'.$alreadyRefunded;
            $existingRefund = Refund::where('idempotency_key', $idempotencyKey)->first();
            if ($existingRefund !== null) {
                return $existingRefund;
            }

            $gateway = app(PaymentManager::class)->driver($txn->gateway);
            $response = $gateway->refundCapture($captureId, $amountCents, $txn->currency, $idempotencyKey);
            $providerStatus = strtoupper((string) ($response['status'] ?? ''));
            abort_unless(in_array($providerStatus, ['COMPLETED', 'PENDING'], true), 502, 'Provider refund was not accepted.');

            $completed = $providerStatus === 'COMPLETED';
            $refund = Refund::create([
                'payment_transaction_id' => $txn->id,
                'amount_cents' => $amountCents,
                'provider_refund_id' => $response['id'] ?? null,
                'idempotency_key' => $idempotencyKey,
                'provider_status' => $providerStatus,
                'reason' => $reason,
                'status' => $completed ? 'completed' : 'pending',
                'completed_at' => $completed ? now() : null,
                'processed_by' => $admin->id,
            ]);

            if ($completed && $alreadyRefunded + $amountCents === $txn->amount_cents) {
                $enrolments = Enrolment::where('order_id', $order->id)
                    ->where('user_id', $order->user_id)
                    ->where('status', 'active')
                    ->lockForUpdate()->get();

                foreach ($enrolments as $enrolment) {
                    $enrolment->update(['status' => 'refunded', 'expires_at' => now()]);
                }

                AuditLog::record('order.refund.entitlements_suspended', $order, [
                    'refund_id' => $refund->id,
                    'enrolment_ids' => $enrolments->modelKeys(),
                    'by' => $admin->id,
                ]);
            }

            AuditLog::record('order.refund', $order, [
                'refund_id' => $refund->id,
                'provider_refund_id' => $refund->provider_refund_id,
                'amount' => $amountCents,
                'status' => $refund->status,
                'by' => $admin->id,
            ]);

            return $refund;
        });
    }
}
