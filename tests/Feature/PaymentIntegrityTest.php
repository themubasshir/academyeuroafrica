<?php

namespace Tests\Feature;

use App\Domain\Checkout\OrderService;
use App\Models\CourseVariant;
use App\Models\Enrolment;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\Payments\Gateways\PayPalGateway;
use App\Services\Payments\PaymentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class PaymentIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_paypal_environment_matches_explicit_mode(): void
    {
        config()->set('services.paypal.mode', 'sandbox');
        $gateway = new PayPalGateway;
        $this->assertFalse($gateway->isLive());
        $this->assertSame('Sandbox', $gateway->environment());

        config()->set('services.paypal.mode', 'live');
        $this->assertTrue($gateway->isLive());
        $this->assertSame('Live', $gateway->environment());
    }

    public function test_invalid_paypal_mode_fails_closed(): void
    {
        config()->set('services.paypal.mode', 'production');
        config()->set('services.paypal.client_id', 'client');
        config()->set('services.paypal.client_secret', 'secret');
        config()->set('services.paypal.webhook_id', 'webhook');

        $gateway = new PayPalGateway;
        $this->assertFalse($gateway->isLive());
        $this->assertFalse($gateway->isConfigured());
        $this->assertSame('Invalid configuration', $gateway->environment());
    }

    public function test_missing_paypal_configuration_is_rejected_before_http(): void
    {
        config()->set('services.paypal.mode', 'sandbox');
        config()->set('services.paypal.client_id', null);
        config()->set('services.paypal.client_secret', null);
        config()->set('services.paypal.webhook_id', null);
        Http::fake();

        $this->expectException(RuntimeException::class);
        app(PaymentManager::class)->driver('paypal');
        Http::assertNothingSent();
    }

    public function test_full_refund_suspends_only_order_entitlements(): void
    {
        config()->set('services.paypal.mode', 'sandbox');
        config()->set('services.paypal.client_id', 'client');
        config()->set('services.paypal.client_secret', 'secret');
        config()->set('services.paypal.webhook_id', 'webhook');

        $student = User::factory()->student()->create();
        $variant = CourseVariant::factory()->create(['price_cents' => 12900]);
        $otherVariant = CourseVariant::factory()->create(['price_cents' => 9900]);
        $service = app(OrderService::class);
        $order = $service->create($student, [$variant->id]);
        $otherOrder = $service->create($student, [$otherVariant->id]);
        $service->fulfil('test', [
            'event_id' => 'paid-integrity', 'type' => 'test.paid',
            'transaction_id' => 'test_txn_'.$order->reference,
            'order_reference' => $order->reference, 'payload' => [],
        ]);
        PaymentTransaction::create([
            'order_id' => $order->id, 'gateway' => 'paypal',
            'gateway_transaction_id' => 'capture-integrity', 'status' => 'paid',
            'amount_cents' => 12900, 'currency' => 'EUR',
            'metadata' => ['capture_id' => 'capture-integrity'],
        ]);
        $service->fulfil('test', [
            'event_id' => 'paid-other', 'type' => 'test.paid',
            'transaction_id' => 'test_txn_'.$otherOrder->reference,
            'order_reference' => $otherOrder->reference, 'payload' => [],
        ]);

        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'token']),
            '*/v2/payments/captures/*/refund' => Http::response(['id' => 'refund-1', 'status' => 'COMPLETED']),
        ]);
        $refund = $service->refund($order->fresh(), User::factory()->superAdmin()->create(), 12900);

        $this->assertSame('completed', $refund->status);
        $this->assertSame('refunded', Enrolment::where('order_id', $order->id)->first()->status);
        $this->assertSame('active', Enrolment::where('order_id', $otherOrder->id)->first()->status);
    }

    public function test_partial_refund_preserves_access_and_provider_pending_is_not_completed(): void
    {
        config()->set('services.paypal.mode', 'sandbox');
        config()->set('services.paypal.client_id', 'client');
        config()->set('services.paypal.client_secret', 'secret');
        config()->set('services.paypal.webhook_id', 'webhook');
        $student = User::factory()->student()->create();
        $variant = CourseVariant::factory()->create(['price_cents' => 12900]);
        $service = app(OrderService::class);
        $order = $service->create($student, [$variant->id]);
        $service->fulfil('test', [
            'event_id' => 'paid-partial', 'type' => 'test.paid',
            'transaction_id' => 'test_txn_'.$order->reference,
            'order_reference' => $order->reference, 'payload' => [],
        ]);
        PaymentTransaction::create([
            'order_id' => $order->id, 'gateway' => 'paypal',
            'gateway_transaction_id' => 'capture-partial', 'status' => 'paid',
            'amount_cents' => 12900, 'currency' => 'EUR',
            'metadata' => ['capture_id' => 'capture-partial'],
        ]);
        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'token']),
            '*/v2/payments/captures/*/refund' => Http::response(['id' => 'refund-2', 'status' => 'PENDING']),
        ]);

        $refund = $service->refund($order->fresh(), User::factory()->superAdmin()->create(), 1000);
        $this->assertSame('pending', $refund->status);
        $this->assertSame('active', Enrolment::first()->status);
        $this->assertSame(0, Enrolment::first()->expires_at?->timestamp ?? 0);
    }
}
