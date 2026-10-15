<?php

namespace App\Domain\Checkout;

use App\Models\AuditLog;
use App\Models\Enrolment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentEvent;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

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
                $variant = \App\Models\CourseVariant::with('course')->findOrFail($variantId);
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
    public function beginPayment(Order $order, \App\Services\Payments\Contracts\PaymentGateway $gateway): array
    {
        abort_unless($order->status === 'pending', 422, 'Order is not payable.');

        $checkout = $gateway->createCheckout($order);

        PaymentTransaction::updateOrCreate(
            ['gateway' => $gateway->name(), 'gateway_transaction_id' => $checkout['transaction_id']],
            [
                'order_id' => $order->id,
                'status' => 'initiated',
                'amount_cents' => $order->total_cents,
                'currency' => $order->currency,
            ]
        );

        return ['order' => $order, 'redirect_url' => $checkout['redirect_url']];
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
            } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                return; // already processed
            }

            if (! in_array($event['type'], ['checkout.session.completed', 'payment.completed', 'test.paid'], true)) {
                PaymentEvent::where('gateway_event_id', $event['event_id'])->update(['processed_at' => now()]);

                return; // not a paid event
            }

            $order = Order::where('reference', $event['order_reference'])->lockForUpdate()->firstOrFail();
            if ($order->isPaid()) {
                PaymentEvent::where('gateway_event_id', $event['event_id'])->update(['processed_at' => now()]);

                return; // already fulfilled
            }

            $txn = PaymentTransaction::where('gateway_transaction_id', $event['transaction_id'])
                ->orWhere('order_id', $order->id)->latest()->first();
            $txn?->update(['status' => 'paid', 'metadata' => ['event_id' => $event['event_id']]]);

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
}
