<?php

namespace Tests\Feature;

use App\Domain\Checkout\OrderService;
use App\Models\CourseVariant;
use App\Models\Enrolment;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebhookReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_capture_marks_payment_failed_without_enrolment(): void
    {
        [$order, $transaction] = $this->pendingPayPalOrder();

        app(OrderService::class)->fulfil('paypal', [
            'event_id' => 'WH-DENIED',
            'type' => 'PAYMENT.CAPTURE.DENIED',
            'transaction_id' => 'PPO-RECONCILE',
            'capture_id' => 'CAP-RECONCILE',
            'order_reference' => $order->reference,
            'payload' => [],
        ]);

        $this->assertSame('failed', $order->fresh()->status);
        $this->assertSame('failed', $transaction->fresh()->status);
        $this->assertDatabaseCount('enrolments', 0);
    }

    public function test_verified_webhook_with_wrong_amount_does_not_enrol(): void
    {
        [$order, $transaction] = $this->pendingPayPalOrder();

        app(OrderService::class)->fulfil('paypal', [
            'event_id' => 'WH-AMOUNT-MISMATCH',
            'type' => 'PAYMENT.CAPTURE.COMPLETED',
            'transaction_id' => 'PPO-RECONCILE',
            'capture_id' => 'CAP-RECONCILE',
            'order_reference' => $order->reference,
            'payload' => [
                'resource' => [
                    'status' => 'COMPLETED',
                    'amount' => ['value' => '1.00', 'currency_code' => 'EUR'],
                ],
            ],
        ]);

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('initiated', $transaction->fresh()->status);
        $this->assertDatabaseCount('enrolments', 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.capture_mismatch']);
    }

    public function test_verified_full_refund_reconciles_transaction_and_expires_entitlement(): void
    {
        [$order, $transaction] = $this->paidPayPalOrder();

        app(OrderService::class)->fulfil('paypal', [
            'event_id' => 'WH-REFUNDED',
            'type' => 'PAYMENT.CAPTURE.REFUNDED',
            'transaction_id' => null,
            'capture_id' => 'CAP-RECONCILE',
            'provider_resource_id' => 'RFD-RECONCILE',
            'order_reference' => null,
            'payload' => [
                'resource' => [
                    'amount' => ['value' => '129.00', 'currency_code' => 'EUR'],
                ],
            ],
        ]);

        $enrolment = Enrolment::where('order_id', $order->id)->firstOrFail();
        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertSame('refunded', $transaction->fresh()->status);
        $this->assertSame('refunded', $enrolment->status);
        $this->assertFalse($enrolment->isActive());
        $this->assertNotNull($enrolment->entitlement_ended_at);
        $this->assertDatabaseHas('refunds', [
            'payment_transaction_id' => $transaction->id,
            'provider_refund_id' => 'RFD-RECONCILE',
            'status' => 'completed',
        ]);
    }

    public function test_duplicate_refund_webhook_is_idempotent(): void
    {
        [$order, $transaction] = $this->paidPayPalOrder();
        $event = [
            'event_id' => 'WH-REFUND-REPLAY',
            'type' => 'PAYMENT.CAPTURE.REFUNDED',
            'transaction_id' => null,
            'capture_id' => 'CAP-RECONCILE',
            'provider_resource_id' => 'RFD-REPLAY',
            'order_reference' => $order->reference,
            'payload' => ['resource' => ['amount' => ['value' => '129.00', 'currency_code' => 'EUR']]],
        ];

        app(OrderService::class)->fulfil('paypal', $event);
        app(OrderService::class)->fulfil('paypal', $event);

        $this->assertSame(1, Refund::where('payment_transaction_id', $transaction->id)->count());
    }

    public function test_chargeback_suspends_entitlement_and_is_visible_on_order(): void
    {
        [$order, $transaction] = $this->paidPayPalOrder();

        app(OrderService::class)->fulfil('paypal', [
            'event_id' => 'WH-DISPUTE',
            'type' => 'CUSTOMER.DISPUTE.CREATED',
            'transaction_id' => null,
            'capture_id' => 'CAP-RECONCILE',
            'provider_resource_id' => 'PP-D-123',
            'order_reference' => null,
            'payload' => ['resource' => ['dispute_life_cycle_stage' => 'CHARGEBACK']],
        ]);

        $enrolment = Enrolment::where('order_id', $order->id)->firstOrFail();
        $this->assertSame('disputed', $order->fresh()->status);
        $this->assertSame('disputed', $transaction->fresh()->status);
        $this->assertSame('suspended', $enrolment->status);
        $this->assertFalse($enrolment->isActive());
    }

    public function test_browser_cancellation_never_enrols_and_marks_owned_order_cancelled(): void
    {
        [$order, $transaction, $student] = $this->pendingPayPalOrder();

        $this->actingAs($student)
            ->get(route('checkout.paypal.cancel', ['token' => 'PPO-RECONCILE']))
            ->assertOk();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('cancelled', $transaction->fresh()->status);
        $this->assertDatabaseCount('enrolments', 0);
    }

    /** @return array{Order, PaymentTransaction, User} */
    private function pendingPayPalOrder(): array
    {
        $student = User::factory()->student()->create();
        $variant = CourseVariant::factory()->create(['price_cents' => 12900]);
        $order = app(OrderService::class)->create($student, [$variant->id]);
        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'gateway' => 'paypal',
            'gateway_transaction_id' => 'PPO-RECONCILE',
            'status' => 'initiated',
            'amount_cents' => 12900,
            'currency' => 'EUR',
            'metadata' => ['capture_id' => 'CAP-RECONCILE'],
        ]);

        return [$order, $transaction, $student];
    }

    /** @return array{Order, PaymentTransaction, User} */
    private function paidPayPalOrder(): array
    {
        [$order, $transaction, $student] = $this->pendingPayPalOrder();
        app(OrderService::class)->fulfil('paypal', [
            'event_id' => 'WH-PAID-'.$order->id,
            'type' => 'PAYMENT.CAPTURE.COMPLETED',
            'transaction_id' => 'PPO-RECONCILE',
            'capture_id' => 'CAP-RECONCILE',
            'order_reference' => $order->reference,
            'payload' => [],
        ]);

        return [$order->fresh(), $transaction->fresh(), $student];
    }
}
