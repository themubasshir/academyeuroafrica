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
use App\Notifications\StudentAccountNotification;
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

        $variantIds = array_values(array_unique(array_map('intval', $variantIds)));

        return DB::transaction(function () use ($user, $variantIds) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            $variants = CourseVariant::with('course')->whereKey($variantIds)->get()->keyBy('id');
            abort_unless($variants->count() === count($variantIds), 404);

            foreach ($variantIds as $variantId) {
                $variant = $variants->get($variantId);
                abort_unless($variant->isPurchasable(), 422, 'Item not purchasable.');

                $existingEnrolment = Enrolment::with('variant')
                    ->where('user_id', $user->id)
                    ->whereHas('variant', fn ($query) => $query->where('course_id', $variant->course_id))
                    ->lockForUpdate()
                    ->first();

                if ($existingEnrolment !== null) {
                    abort_unless(
                        $existingEnrolment->course_variant_id === $variant->id
                            && $existingEnrolment->source === 'purchased'
                            && $existingEnrolment->isExpired()
                            && $variant->renewable,
                        422,
                        'You already have an enrolment for this course.'
                    );
                }
            }

            if (count($variantIds) === 1) {
                $pendingOrder = Order::query()
                    ->where('user_id', $user->id)
                    ->where('status', 'pending')
                    ->whereHas('items', fn ($query) => $query->where('course_variant_id', $variantIds[0]))
                    ->has('items', '=', 1)
                    ->latest('id')
                    ->first();

                if ($pendingOrder !== null) {
                    return $pendingOrder;
                }
            }

            $originalOrder = Enrolment::with('order')
                ->whereIn('course_variant_id', $variantIds)
                ->where('user_id', $user->id)
                ->where('source', 'purchased')
                ->whereNotNull('order_id')
                ->latest('id')->first();

            $order = Order::create([
                'user_id' => $user->id,
                'reference' => 'EAMN-'.strtoupper(Str::random(10)),
                'status' => 'pending',
                'kind' => $originalOrder ? 'renewal' : 'purchase',
                'renewal_of_order_id' => $originalOrder?->order_id,
                'total_cents' => 0,
                'currency' => 'EUR',
            ]);

            $total = 0;
            foreach ($variantIds as $variantId) {
                $variant = $variants->get($variantId);

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

            $transaction = $this->resolveTransaction($gatewayName, $event);
            $order = $this->resolveOrder($event, $transaction);

            if ($event['type'] === 'PAYMENT.CAPTURE.PENDING') {
                $transaction?->update(['status' => 'pending']);
                $this->markEventProcessed($event['event_id']);

                return;
            }

            if (in_array($event['type'], ['PAYMENT.CAPTURE.DENIED', 'CHECKOUT.ORDER.VOIDED', 'CHECKOUT.PAYMENT-APPROVAL.REVERSED'], true)) {
                $status = $event['type'] === 'PAYMENT.CAPTURE.DENIED' ? 'failed' : 'cancelled';
                $transaction?->update(['status' => $status]);
                if ($order !== null && ! $order->isPaid()) {
                    $order->update(['status' => $status]);
                }
                $this->markEventProcessed($event['event_id']);

                return;
            }

            if ($event['type'] === 'PAYMENT.CAPTURE.REFUNDED') {
                $this->reconcileWebhookRefund($gatewayName, $event, $transaction, $order);
                $this->markEventProcessed($event['event_id']);

                return;
            }

            if (in_array($event['type'], ['CUSTOMER.DISPUTE.CREATED', 'CUSTOMER.DISPUTE.UPDATED', 'CUSTOMER.DISPUTE.RESOLVED'], true)) {
                $this->reconcileDispute($gatewayName, $event, $transaction, $order);
                $this->markEventProcessed($event['event_id']);

                return;
            }

            if (! in_array($event['type'], $paidTypes, true)) {
                $this->markEventProcessed($event['event_id']);

                return; // not a paid event
            }

            if ($order === null) {
                $this->markEventProcessed($event['event_id']);

                return; // unresolvable — recorded for reconciliation
            }
            if ($gatewayName === 'paypal'
                && $event['type'] === 'PAYMENT.CAPTURE.COMPLETED'
                && ! $this->paypalCaptureMatchesOrder($event, $order)) {
                $this->markEventProcessed($event['event_id']);
                AuditLog::record('payment.capture_mismatch', $order, [
                    'gateway' => $gatewayName,
                    'event' => $event['event_id'],
                ]);

                return;
            }
            if ($order->isPaid()) {
                $this->markEventProcessed($event['event_id']);

                return; // already fulfilled
            }

            $transaction ??= PaymentTransaction::where('order_id', $order->id)->latest()->first();
            $transaction?->update([
                'status' => 'paid',
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'event_id' => $event['event_id'],
                    // capture id for refunds — for paypal event_id is the capture id
                    'capture_id' => $event['capture_id'] ?? $event['event_id'],
                ]),
            ]);

            $order->update(['status' => 'paid']);

            foreach ($order->items as $item) {
                $variant = $item->variant;
                $activation = now();
                $existing = Enrolment::where('user_id', $order->user_id)
                    ->where('course_variant_id', $item->course_variant_id)
                    ->lockForUpdate()->first();
                $previousEnd = $existing?->entitlement_ended_at ?? $existing?->expires_at;
                $expiry = $existing
                    ? $variant->renewalExpiry($previousEnd, $activation)
                    : $variant->expiryFrom($activation);

                if ($existing) {
                    // Renewal: keep the same enrolment row so progress, grades and
                    // certificates survive; extend the entitlement window.
                    $existing->forceFill([
                        'order_id' => $order->id,
                        'source' => 'purchased',
                        'status' => 'active',
                        'activated_at' => $activation,
                        'entitlement_started_at' => $existing->entitlement_started_at ?? $activation,
                        'entitlement_ended_at' => $expiry,
                        'expires_at' => $expiry,
                    ])->save();
                    $enrolment = $existing;
                    $isRenewal = true;
                } else {
                    $enrolment = Enrolment::create([
                        'user_id' => $order->user_id,
                        'course_variant_id' => $item->course_variant_id,
                        'order_id' => $order->id,
                        'source' => 'purchased',
                        'status' => 'active',
                        'enrolled_at' => $activation,
                        'activated_at' => $activation,
                        'entitlement_started_at' => $activation,
                        'entitlement_ended_at' => $expiry,
                        'expires_at' => $expiry,
                    ]);
                    $isRenewal = false;
                }

                $order->user->notify(new StudentAccountNotification(
                    $isRenewal ? 'Enrolment renewed' : 'Enrolment confirmed',
                    $isRenewal
                        ? 'Your access to '.$item->name.' has been renewed.'
                        : 'Your enrolment in '.$item->name.' is now active.',
                    route('student.dashboard'),
                ));
            }

            $this->markEventProcessed($event['event_id']);
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

            $gateway = app(PaymentManager::class)->driver($txn->gateway, requireEnabled: false);
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
                    $enrolment->update([
                        'status' => 'refunded',
                        'entitlement_ended_at' => now(),
                        'expires_at' => now(),
                    ]);
                }

                $order->update(['status' => 'refunded']);

                $order->user->notify(new StudentAccountNotification(
                    'Refund completed',
                    'Your refund for order '.$order->reference.' has been completed and course access has been suspended.',
                    route('student.orders'),
                    'refunds',
                ));

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

    private function resolveTransaction(string $gatewayName, array $event): ?PaymentTransaction
    {
        if (filled($event['transaction_id'] ?? null)) {
            $transaction = PaymentTransaction::where('gateway', $gatewayName)
                ->where('gateway_transaction_id', $event['transaction_id'])
                ->lockForUpdate()
                ->first();
            if ($transaction !== null) {
                return $transaction;
            }
        }

        if (filled($event['capture_id'] ?? null)) {
            return PaymentTransaction::where('gateway', $gatewayName)
                ->where('metadata->capture_id', $event['capture_id'])
                ->lockForUpdate()
                ->first();
        }

        return null;
    }

    private function resolveOrder(array $event, ?PaymentTransaction $transaction): ?Order
    {
        if (filled($event['order_reference'] ?? null)) {
            $order = Order::where('reference', $event['order_reference'])->lockForUpdate()->first();
            if ($order !== null) {
                return $order;
            }
        }

        return $transaction !== null
            ? Order::lockForUpdate()->find($transaction->order_id)
            : null;
    }

    private function reconcileWebhookRefund(
        string $gatewayName,
        array $event,
        ?PaymentTransaction $transaction,
        ?Order $order,
    ): void {
        if ($transaction === null || $order === null) {
            return;
        }

        $resource = $event['payload']['resource'] ?? [];
        $currency = (string) ($resource['amount']['currency_code'] ?? '');
        $amountCents = (int) round(((float) ($resource['amount']['value'] ?? 0)) * 100);

        if ($amountCents <= 0 || $currency !== $transaction->currency) {
            AuditLog::record('payment.refund_webhook_unresolved', $order, [
                'gateway' => $gatewayName,
                'event' => $event['event_id'],
            ]);

            return;
        }

        Refund::firstOrCreate(
            [
                'payment_transaction_id' => $transaction->id,
                'provider_refund_id' => $event['provider_resource_id'] ?? $event['event_id'],
            ],
            [
                'amount_cents' => $amountCents,
                'idempotency_key' => 'webhook:'.$event['event_id'],
                'provider_status' => 'COMPLETED',
                'reason' => 'PayPal refund notification',
                'status' => 'completed',
                'completed_at' => now(),
            ]
        );

        $refundedCents = Refund::where('payment_transaction_id', $transaction->id)
            ->where('status', 'completed')
            ->sum('amount_cents');

        if ($refundedCents >= $transaction->amount_cents) {
            $transaction->update(['status' => 'refunded']);
            $order->update(['status' => 'refunded']);
            $this->endOrderEntitlements($order, 'refunded');
        }

        AuditLog::record('payment.refund_webhook_reconciled', $order, [
            'gateway' => $gatewayName,
            'event' => $event['event_id'],
            'amount' => $amountCents,
            'full_refund' => $refundedCents >= $transaction->amount_cents,
        ]);
    }

    private function reconcileDispute(
        string $gatewayName,
        array $event,
        ?PaymentTransaction $transaction,
        ?Order $order,
    ): void {
        if ($transaction === null || $order === null) {
            return;
        }

        $isResolved = $event['type'] === 'CUSTOMER.DISPUTE.RESOLVED';
        $transaction->update(['status' => $isResolved ? 'dispute_resolved' : 'disputed']);
        $order->update(['status' => $isResolved ? 'dispute_resolved' : 'disputed']);

        if (! $isResolved) {
            $this->endOrderEntitlements($order, 'suspended');
        }

        AuditLog::record('payment.dispute_reconciled', $order, [
            'gateway' => $gatewayName,
            'event' => $event['event_id'],
            'type' => $event['type'],
            'dispute_id' => $event['provider_resource_id'] ?? null,
        ]);
    }

    private function endOrderEntitlements(Order $order, string $status): void
    {
        Enrolment::where('order_id', $order->id)
            ->where('user_id', $order->user_id)
            ->where('status', 'active')
            ->lockForUpdate()
            ->get()
            ->each(fn (Enrolment $enrolment) => $enrolment->update([
                'status' => $status,
                'entitlement_ended_at' => now(),
                'expires_at' => now(),
            ]));
    }

    private function markEventProcessed(string $eventId): void
    {
        PaymentEvent::where('gateway_event_id', $eventId)->update(['processed_at' => now()]);
    }

    private function paypalCaptureMatchesOrder(array $event, Order $order): bool
    {
        $resource = $event['payload']['resource'] ?? null;
        if (! is_array($resource) || ! isset($resource['amount'])) {
            return true;
        }

        $amountCents = (int) round(((float) ($resource['amount']['value'] ?? 0)) * 100);

        return ($resource['status'] ?? null) === 'COMPLETED'
            && $amountCents === $order->total_cents
            && ($resource['amount']['currency_code'] ?? null) === $order->currency;
    }
}
