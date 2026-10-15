<?php

namespace App\Services\Payments\Gateways;

use App\Models\Order;
use App\Services\Payments\Contracts\PaymentGateway;
use App\Services\Payments\InvalidWebhookSignature;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * PayPal Checkout — REST Orders v2, CAPTURE intent. No card storage.
 *
 * Flow:
 *   createCheckout → POST /v2/checkout/orders → approval_url redirect
 *   browser return → captureOrder() → server-side capture + fulfilment
 *   webhook        → verify via PayPal's verify-webhook-signature endpoint
 *
 * Credentials: PAYPAL_CLIENT_ID / PAYPAL_CLIENT_SECRET / PAYPAL_WEBHOOK_ID.
 * PAYPAL_MODE=sandbox uses api-m.sandbox.paypal.com; live uses api-m.paypal.com.
 * Sandbox credentials are never sent to the production base URL.
 */
class PayPalGateway implements PaymentGateway
{
    private const SANDBOX = 'https://api-m.sandbox.paypal.com';

    private const LIVE = 'https://api-m.paypal.com';

    private ?string $accessToken = null;

    public function name(): string
    {
        return 'paypal';
    }

    public function isLive(): bool
    {
        return true;
    }

    public function baseUrl(): string
    {
        return config('services.paypal.mode', 'sandbox') === 'live'
            ? self::LIVE
            : self::SANDBOX;
    }

    /**
     * Create a PayPal order and return the approval redirect URL.
     * Amount/currency come from the order server-side; custom_id carries the
     * internal reference for reconciliation.
     *
     * @return array{redirect_url:string, transaction_id:string}
     */
    public function createCheckout(Order $order): array
    {
        $currency = $order->currency;
        $amount = number_format($order->total_cents / 100, 2, '.', '');

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $order->reference,
                'custom_id' => $order->reference,
                'amount' => [
                    'currency_code' => $currency,
                    'value' => $amount,
                ],
            ]],
            'application_context' => [
                'brand_name' => config('services.paypal.brand_name', 'EAMN Academy'),
                'return_url' => route('checkout.paypal.return'),
                'cancel_url' => route('checkout.paypal.cancel'),
                'user_action' => 'PAY_NOW',
            ],
        ];

        // Idempotency: same order reference = same request id, so retries and
        // repeated browser submits don't create duplicate PayPal orders.
        $response = $this->authed()
            ->withHeader('PayPal-Request-Id', $order->reference)
            ->post($this->baseUrl().'/v2/checkout/orders', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('PayPal order creation failed: '.$response->body());
        }

        $data = $response->json();
        $approve = collect($data['links'] ?? [])->firstWhere('rel', 'approve');

        if (! $approve) {
            throw new RuntimeException('PayPal approval link missing.');
        }

        return [
            'redirect_url' => $approve['href'],
            'transaction_id' => $data['id'], // PayPal order id
        ];
    }

    /**
     * Capture an approved PayPal order. Returns the normalised fulfilment event
     * when the capture is COMPLETED; throws on pending/denied/mismatch.
     *
     * @return array{event_id:string, type:string, transaction_id:?string, order_reference:?string, payload:array}
     */
    public function captureOrder(string $paypalOrderId, Order $order): array
    {
        $response = $this->authed()
            ->withHeader('PayPal-Request-Id', $order->reference.':capture')
            ->post($this->baseUrl()."/v2/checkout/orders/{$paypalOrderId}/capture", (object) []);

        if (! $response->successful()) {
            throw new RuntimeException('PayPal capture failed: '.$response->body());
        }

        $data = $response->json();
        $unit = $data['purchase_units'][0] ?? [];
        $capture = $unit['payments']['captures'][0] ?? [];

        $this->assertCaptureValid($data, $capture, $order);

        return [
            'event_id' => $capture['id'],
            'type' => 'payment.completed',
            'transaction_id' => $paypalOrderId,
            'order_reference' => $order->reference,
            'payload' => $data,
        ];
    }

    /**
     * Retrieve current PayPal order status (for reconciliation / pending).
     */
    public function getOrder(string $paypalOrderId): array
    {
        $response = $this->authed()->get($this->baseUrl()."/v2/checkout/orders/{$paypalOrderId}");
        if (! $response->successful()) {
            throw new RuntimeException('PayPal order lookup failed: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Refund a completed capture. Returns the decoded refund response.
     * PayPal refunds are idempotent via PayPal-Request-Id.
     */
    public function refundCapture(string $captureId, int $amountCents, string $currency, string $idempotencyKey): array
    {
        $response = $this->authed()
            ->withHeader('PayPal-Request-Id', $idempotencyKey)
            ->post($this->baseUrl()."/v2/payments/captures/{$captureId}/refund", [
                'amount' => [
                    'value' => number_format($amountCents / 100, 2, '.', ''),
                    'currency_code' => $currency,
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('PayPal refund failed: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Verify an inbound webhook via PayPal's official
     * /v1/notifications/verify-webhook-signature mechanism (not HMAC).
     *
     * @return array{event_id:string, type:string, transaction_id:?string, order_reference:?string, payload:array}
     */
    public function verifyWebhook(Request $request): array
    {
        $webhookId = config('services.paypal.webhook_id')
            ?: throw new RuntimeException('PAYPAL_WEBHOOK_ID not configured.');

        $headers = [
            'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'),
            'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
            'cert_url' => $request->header('PAYPAL-CERT-URL'),
            'auth_algo' => $request->header('PAYPAL-AUTH-ALGO'),
            'transmission_sig' => $request->header('PAYPAL-TRANSMISSION-SIG'),
        ];

        if (in_array(null, $headers, true)) {
            throw new InvalidWebhookSignature('Missing PayPal transmission headers.');
        }

        $event = json_decode($request->getContent(), true);
        if (! is_array($event) || empty($event['id'])) {
            throw new InvalidWebhookSignature('Malformed PayPal webhook body.');
        }

        $verification = $this->authed()->post(
            $this->baseUrl().'/v1/notifications/verify-webhook-signature',
            [
                'webhook_id' => $webhookId,
                'webhook_event' => $event,
                ...$headers,
            ]
        );

        if (! $verification->successful()
            || $verification->json('verification_status') !== 'SUCCESS') {
            throw new InvalidWebhookSignature('PayPal webhook verification failed.');
        }

        $resource = $event['resource'] ?? [];
        // For CAPTURE.* events the resource is the capture; the paypal order id
        // (our stored gateway_transaction_id) is under related_ids.order_id.
        $paypalOrderId = $resource['supplementary_data']['related_ids']['order_id']
            ?? $resource['id'] ?? null;

        return [
            'event_id' => $event['id'],
            'type' => $event['event_type'] ?? '',
            // capture id stored for refunds; gateway txn lookup uses order id
            'transaction_id' => $paypalOrderId,
            'capture_id' => $resource['id'] ?? null,
            'order_reference' => $resource['custom_id'] ?? $resource['custom'] ?? null,
            'payload' => $event,
        ];
    }

    // ------------------------------------------------------------------

    /**
     * Enforce capture + order integrity before fulfilment. Throws on any
     * mismatch so a tampered/foreign capture can't fulfil an order.
     */
    private function assertCaptureValid(array $orderData, array $capture, Order $order): void
    {
        $status = $capture['status'] ?? ($orderData['status'] ?? null);
        if ($status !== 'COMPLETED') {
            throw new RuntimeException('PayPal capture not completed: '.($status ?? 'unknown'));
        }

        $amount = $capture['amount'] ?? [];
        if ((int) round(((float) ($amount['value'] ?? 0)) * 100) !== $order->total_cents) {
            throw new RuntimeException('PayPal amount mismatch.');
        }
        if (($amount['currency_code'] ?? null) !== $order->currency) {
            throw new RuntimeException('PayPal currency mismatch.');
        }

        $reference = $orderData['purchase_units'][0]['custom_id']
            ?? $orderData['purchase_units'][0]['reference_id'] ?? null;
        if ($reference !== $order->reference) {
            throw new RuntimeException('PayPal order reference mismatch.');
        }
    }

    private function authed(): PendingRequest
    {
        return Http::withToken($this->accessToken())
            ->acceptJson()
            ->asJson();
    }

    /**
     * OAuth2 client-credentials token. Cached for the request lifetime;
     * PayPal tokens are short-lived (~9 h) but we only hold it in-memory.
     */
    private function accessToken(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $clientId = config('services.paypal.client_id')
            ?: throw new RuntimeException('PAYPAL_CLIENT_ID not configured.');
        $secret = config('services.paypal.client_secret')
            ?: throw new RuntimeException('PAYPAL_CLIENT_SECRET not configured.');

        $response = Http::asForm()
            ->withBasicAuth($clientId, $secret)
            ->post($this->baseUrl().'/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('PayPal auth failed: '.$response->body());
        }

        return $this->accessToken = $response->json('access_token');
    }
}
