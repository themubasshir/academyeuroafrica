<?php

namespace Tests\Feature\Payments;

use App\Domain\Checkout\OrderService;
use App\Models\Course;
use App\Models\CourseVariant;
use App\Models\Enrolment;
use App\Models\Order;
use App\Models\PaymentEvent;
use App\Models\PaymentGatewaySetting;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\Payments\Gateways\PayPalGateway;
use App\Services\Payments\InvalidWebhookSignature;
use App\Services\Payments\PaymentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * PayPal Orders v2 — all calls are HTTP-faked. These tests prove our
 * integration logic, not a real PayPal transaction.
 */
class PayPalGatewayTest extends TestCase
{
    use RefreshDatabase;

    private PayPalGateway $gateway;

    private User $student;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.paypal.enabled', true);
        config()->set('services.paypal.mode', 'sandbox');
        config()->set('services.paypal.client_id', 'sb-client');
        config()->set('services.paypal.client_secret', 'sb-secret');
        config()->set('services.paypal.webhook_id', 'WH-TEST');
        config()->set('services.paypal.brand_name', 'EAMN Academy');

        $this->gateway = new PayPalGateway;
        $this->student = User::factory()->student()->create();

        $variant = CourseVariant::factory()->for(Course::factory())->create(['price_cents' => 12900]);
        $this->order = app(OrderService::class)->create($this->student, [$variant->id]);
    }

    // ---- helpers ----

    /** Single Http::fake call covering token + any extra endpoints. */
    private function fakePaypal(array $extra): void
    {
        Http::fake(array_merge([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'tok-123', 'expires_in' => 32000]),
        ], $extra));
    }

    private function fakeCreateOrder(string $paypalId = 'PPO-1'): void
    {
        $this->fakePaypal([
            '*/v2/checkout/orders' => Http::response([
                'id' => $paypalId,
                'links' => [
                    ['rel' => 'approve', 'href' => 'https://sandbox.paypal.com/checkoutnow?token='.$paypalId],
                ],
            ], 201),
        ]);
    }

    private function fakeCapture(string $status = 'COMPLETED', int $cents = 12900, string $currency = 'EUR', ?string $reference = null): void
    {
        $reference ??= $this->order->reference;
        $this->fakePaypal([
            '*/v2/checkout/orders/*/capture' => Http::response([
                'id' => 'PPO-1',
                'status' => $status,
                'purchase_units' => [[
                    'custom_id' => $reference,
                    'payments' => ['captures' => [[
                        'id' => 'CAP-9',
                        'status' => $status,
                        'amount' => ['value' => number_format($cents / 100, 2, '.', ''), 'currency_code' => $currency],
                    ]]],
                ]],
            ]),
        ]);
    }

    /** Seed the initiated transaction the way beginPayment() would. */
    private function seedTransaction(string $paypalId = 'PPO-1'): void
    {
        PaymentTransaction::create([
            'order_id' => $this->order->id, 'gateway' => 'paypal',
            'gateway_transaction_id' => $paypalId, 'amount_cents' => 12900,
            'currency' => 'EUR', 'status' => 'initiated',
        ]);
    }

    // ---- 1-4: create + auth ----

    public function test_creates_paypal_order_and_returns_approval_url(): void
    {
        $this->fakeCreateOrder('PPO-ABC');
        $checkout = $this->gateway->createCheckout($this->order);

        $this->assertSame('PPO-ABC', $checkout['transaction_id']);
        $this->assertStringContainsString('sandbox.paypal.com', $checkout['redirect_url']);
        Http::assertSent(fn ($r) => $r->hasHeader('PayPal-Request-Id', $this->order->reference)
            && $r['intent'] === 'CAPTURE'
            && $r['purchase_units'][0]['custom_id'] === $this->order->reference
            && $r['purchase_units'][0]['amount']['value'] === '129.00');
    }

    public function test_oauth_token_request_uses_client_credentials(): void
    {
        $this->fakeCreateOrder();
        $this->gateway->createCheckout($this->order); // triggers token fetch first
        Http::assertSent(fn ($r) => str_contains($r->url(), '/v1/oauth2/token'));
    }

    public function test_invalid_credentials_throw(): void
    {
        Http::fake(['*/v1/oauth2/token' => Http::response(['error' => 'invalid_client'], 401)]);
        $this->expectException(\RuntimeException::class);
        $this->gateway->createCheckout($this->order);
    }

    public function test_sandbox_mode_never_hits_live_url(): void
    {
        config()->set('services.paypal.mode', 'sandbox');
        $this->assertSame('https://api-m.sandbox.paypal.com', $this->gateway->baseUrl());
        config()->set('services.paypal.mode', 'live');
        $this->assertSame('https://api-m.paypal.com', (new PayPalGateway)->baseUrl());
    }

    // ---- 6-11: capture validation ----

    public function test_successful_capture_returns_paid_event(): void
    {
        $this->fakeCapture('COMPLETED');
        $event = $this->gateway->captureOrder('PPO-1', $this->order);
        $this->assertSame('payment.completed', $event['type']);
        $this->assertSame('CAP-9', $event['event_id']);
    }

    public function test_pending_capture_rejected(): void
    {
        $this->fakeCapture('PENDING');
        $this->expectException(\RuntimeException::class);
        $this->gateway->captureOrder('PPO-1', $this->order);
    }

    public function test_denied_capture_rejected(): void
    {
        $this->fakeCapture('DENIED');
        $this->expectException(\RuntimeException::class);
        $this->gateway->captureOrder('PPO-1', $this->order);
    }

    public function test_amount_mismatch_rejected(): void
    {
        $this->fakeCapture('COMPLETED', cents: 100);
        $this->expectException(\RuntimeException::class);
        $this->gateway->captureOrder('PPO-1', $this->order);
    }

    public function test_currency_mismatch_rejected(): void
    {
        $this->fakeCapture('COMPLETED', currency: 'USD');
        $this->expectException(\RuntimeException::class);
        $this->gateway->captureOrder('PPO-1', $this->order);
    }

    public function test_reference_mismatch_rejected(): void
    {
        $this->fakeCapture('COMPLETED', reference: 'EAMN-OTHER');
        $this->expectException(\RuntimeException::class);
        $this->gateway->captureOrder('PPO-1', $this->order);
    }

    // ---- 13,19,20: capture → fulfilment → enrolment ----

    public function test_capture_then_fulfil_creates_enrolment_once(): void
    {
        $this->seedTransaction();
        $this->fakeCapture('COMPLETED');
        $event = $this->gateway->captureOrder('PPO-1', $this->order);

        $service = app(OrderService::class);
        $service->fulfil('paypal', $event);
        $service->fulfil('paypal', $event); // duplicate — must be no-op

        $this->assertTrue($this->order->fresh()->isPaid());
        $this->assertSame(1, Enrolment::where('user_id', $this->student->id)->count());
        $this->assertSame('paid', PaymentTransaction::where('order_id', $this->order->id)->first()->status);
    }

    // ---- 14-16: webhook verification ----

    private function webhookRequest(array $event, bool $verified = true, bool $withHeaders = true): Request
    {
        $this->fakePaypal([
            '*/v1/notifications/verify-webhook-signature' => Http::response([
                'verification_status' => $verified ? 'SUCCESS' : 'FAILURE',
            ]),
        ]);

        $request = Request::create('/webhooks/paypal', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($event));

        if ($withHeaders) {
            $request->headers->set('PAYPAL-TRANSMISSION-ID', 't1');
            $request->headers->set('PAYPAL-TRANSMISSION-TIME', '2026-10-15T10:00:00Z');
            $request->headers->set('PAYPAL-CERT-URL', 'https://api.paypal.com/cert.pem');
            $request->headers->set('PAYPAL-AUTH-ALGO', 'SHA256withRSA');
            $request->headers->set('PAYPAL-TRANSMISSION-SIG', 'sig');
        }

        return $request;
    }

    public function test_valid_webhook_verified_and_normalised(): void
    {
        $event = [
            'id' => 'WH-1',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'CAP-9',
                'custom_id' => $this->order->reference,
                'supplementary_data' => ['related_ids' => ['order_id' => 'PPO-1']],
            ],
        ];
        $result = $this->gateway->verifyWebhook($this->webhookRequest($event));

        $this->assertSame('WH-1', $result['event_id']);
        $this->assertSame('PAYMENT.CAPTURE.COMPLETED', $result['type']);
        $this->assertSame('PPO-1', $result['transaction_id']);
        $this->assertSame('CAP-9', $result['capture_id']);
    }

    public function test_invalid_signature_rejected(): void
    {
        $this->expectException(InvalidWebhookSignature::class);
        $this->gateway->verifyWebhook($this->webhookRequest(['id' => 'WH-1', 'resource' => []], verified: false));
    }

    public function test_missing_headers_rejected(): void
    {
        $this->expectException(InvalidWebhookSignature::class);
        $this->gateway->verifyWebhook($this->webhookRequest(['id' => 'WH-1'], withHeaders: false));
    }

    public function test_webhook_verification_uses_the_active_environments_webhook_id(): void
    {
        PaymentGatewaySetting::create([
            'gateway' => 'paypal',
            'enabled' => true,
            'environment' => 'live',
            'sandbox_client_id' => 'sandbox-client',
            'sandbox_client_secret' => 'sandbox-secret',
            'sandbox_webhook_id' => 'WH-SANDBOX',
            'live_client_id' => 'live-client',
            'live_client_secret' => 'live-secret',
            'live_webhook_id' => 'WH-LIVE',
        ]);
        Http::fake([
            'https://api-m.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'live-token']),
            'https://api-m.paypal.com/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS']),
        ]);
        $request = Request::create('/webhooks/paypal', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['id' => 'WH-LIVE-EVENT', 'event_type' => 'PAYMENT.CAPTURE.PENDING', 'resource' => []]));
        foreach ([
            'PAYPAL-TRANSMISSION-ID' => 'transmission-id',
            'PAYPAL-TRANSMISSION-TIME' => '2026-10-18T12:00:00Z',
            'PAYPAL-CERT-URL' => 'https://api.paypal.com/cert.pem',
            'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
            'PAYPAL-TRANSMISSION-SIG' => 'signature',
        ] as $header => $value) {
            $request->headers->set($header, $value);
        }

        (new PayPalGateway)->verifyWebhook($request);

        Http::assertSent(fn ($sentRequest) => str_contains($sentRequest->url(), '/verify-webhook-signature')
            && $sentRequest['webhook_id'] === 'WH-LIVE');
        Http::assertNotSent(fn ($sentRequest) => str_contains($sentRequest->url(), 'sandbox.paypal.com'));
    }

    public function test_missing_active_environment_webhook_id_is_rejected(): void
    {
        PaymentGatewaySetting::create([
            'gateway' => 'paypal',
            'enabled' => true,
            'environment' => 'live',
            'live_client_id' => 'live-client',
            'live_client_secret' => 'live-secret',
        ]);

        $this->expectException(\RuntimeException::class);
        (new PayPalGateway)->verifyWebhook($this->webhookRequest(['id' => 'WH-1']));
    }

    public function test_repeated_webhook_event_idempotent(): void
    {
        $this->seedTransaction();
        $service = app(OrderService::class);
        $event = ['event_id' => 'WH-1', 'type' => 'PAYMENT.CAPTURE.COMPLETED', 'transaction_id' => 'PPO-1', 'order_reference' => $this->order->reference, 'payload' => []];

        $service->fulfil('paypal', $event);
        $service->fulfil('paypal', $event); // replay — unique index makes it a no-op

        $this->assertSame(1, PaymentEvent::where('gateway_event_id', 'WH-1')->count());
        $this->assertSame(1, Enrolment::count());
    }

    // ---- 17: return-before-webhook race ----

    public function test_return_capture_then_late_webhook_both_fulfil_once(): void
    {
        $this->seedTransaction();
        $service = app(OrderService::class);

        // capture-on-return wins the race
        $service->fulfil('paypal', ['event_id' => 'CAP-9', 'type' => 'payment.completed', 'transaction_id' => 'PPO-1', 'order_reference' => $this->order->reference, 'payload' => []]);
        // webhook arrives later with different event id — order already paid
        $service->fulfil('paypal', ['event_id' => 'WH-77', 'type' => 'PAYMENT.CAPTURE.COMPLETED', 'transaction_id' => 'PPO-1', 'order_reference' => $this->order->reference, 'payload' => []]);

        $this->assertSame(1, Enrolment::count());
        $this->assertTrue($this->order->fresh()->isPaid());
    }

    // ---- 21-22: refunds ----

    public function test_refund_success(): void
    {
        $this->seedTransaction();
        $this->fakeCapture('COMPLETED');
        $event = $this->gateway->captureOrder('PPO-1', $this->order);
        app(OrderService::class)->fulfil('paypal', $event);

        $this->fakePaypal(['*/v2/payments/captures/*/refund' => Http::response(['id' => 'RFD-1', 'status' => 'COMPLETED'])]);

        $refund = app(OrderService::class)->refund($this->order->fresh(), User::factory()->superAdmin()->create(), 12900);
        $this->assertSame('completed', $refund->status);
        $this->assertDatabaseHas('refunds', ['amount_cents' => 12900, 'status' => 'completed']);
    }

    public function test_refund_failure_throws(): void
    {
        $this->seedTransaction();
        $this->fakeCapture('COMPLETED');
        $event = $this->gateway->captureOrder('PPO-1', $this->order);
        app(OrderService::class)->fulfil('paypal', $event);

        $this->fakePaypal(['*/v2/payments/captures/*/refund' => Http::response(['name' => 'RESOURCE_NOT_FOUND'], 404)]);

        $this->expectException(\RuntimeException::class);
        app(OrderService::class)->refund($this->order->fresh(), User::factory()->superAdmin()->create(), 12900);
    }

    // ---- 23: cross-user order access ----

    public function test_other_user_cannot_use_paypal_return(): void
    {
        $this->seedTransaction();
        $intruder = User::factory()->student()->create();

        $this->actingAs($intruder)
            ->get(route('checkout.paypal.return', ['token' => 'PPO-1']))
            ->assertForbidden();
    }

    // ---- 24: gateway selection safety ----

    public function test_default_gateway_is_paypal(): void
    {
        config()->set('services.paypal.enabled', true);
        $this->assertSame('paypal', config('services.payments.default'));
        $this->assertInstanceOf(PayPalGateway::class, app(PaymentManager::class)->driver());
    }
}
