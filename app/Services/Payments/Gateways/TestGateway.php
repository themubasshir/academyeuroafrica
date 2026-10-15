<?php

namespace App\Services\Payments\Gateways;

use App\Models\Order;
use App\Services\Payments\Contracts\PaymentGateway;
use App\Services\Payments\InvalidWebhookSignature;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Development/test adapter ONLY. Registered exclusively when APP_ENV is
 * `local`/`testing` and never in production. Simulates a hosted checkout by
 * immediately returning a confirm URL; its "webhook" is an HMAC-signed POST
 * using the local test secret — never used to fulfil real orders.
 */
class TestGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'test';
    }

    public function isLive(): bool
    {
        return false;
    }

    public function createCheckout(Order $order): array
    {
        return [
            'redirect_url' => route('test-checkout.show', $order->reference),
            'transaction_id' => 'test_txn_'.$order->reference,
        ];
    }

    public function verifyWebhook(Request $request): array
    {
        $secret = config('services.test_gateway.secret', 'test-secret');
        $body = $request->getContent();
        $signature = $request->header('X-Test-Signature')
            ?: throw new InvalidWebhookSignature('Missing test signature.');

        if (! hash_equals(hash_hmac('sha256', $body, $secret), $signature)) {
            throw new InvalidWebhookSignature('Bad test signature.');
        }

        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        return [
            'event_id' => $data['event_id'],
            'type' => $data['type'],
            'transaction_id' => $data['transaction_id'] ?? null,
            'order_reference' => $data['order_reference'] ?? null,
            'payload' => $data,
        ];
    }
}
