<?php

namespace App\Services\Payments\Gateways;

use App\Models\Order;
use App\Services\Payments\Contracts\PaymentGateway;
use App\Services\Payments\InvalidWebhookSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Stripe adapter — hosted Checkout, server-verified webhooks, no card storage.
 * Uses the REST API directly to avoid the stripe-php dependency; swap to the
 * SDK if more Stripe features are needed later.
 */
class StripeGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'stripe';
    }

    public function isLive(): bool
    {
        return true;
    }

    public function createCheckout(Order $order): array
    {
        $secret = config('services.stripe.secret')
            ?: throw new RuntimeException('Stripe secret not configured.');

        $lineItems = $order->items->map(fn ($item, $i) => [
            "line_items[$i][price_data][currency]" => strtolower($order->currency),
            "line_items[$i][price_data][unit_amount]" => $item->unit_price_cents,
            "line_items[$i][price_data][product_data][name]" => $item->name,
            "line_items[$i][quantity]" => $item->quantity,
        ])->collapse()->all();

        $response = Http::withBasicAuth($secret, '')
            ->asForm()
            ->post('https://api.stripe.com/v1/checkout/sessions', array_merge($lineItems, [
                'mode' => 'payment',
                'success_url' => route('checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('checkout.cancelled'),
                'client_reference_id' => $order->reference,
                'metadata[order_reference]' => $order->reference,
            ]));

        if (! $response->successful()) {
            throw new RuntimeException('Stripe checkout failed: '.$response->body());
        }

        return [
            'redirect_url' => $response->json('url'),
            'transaction_id' => $response->json('id'),
        ];
    }

    public function verifyWebhook(Request $request): array
    {
        $secret = config('services.stripe.webhook_secret')
            ?: throw new RuntimeException('Stripe webhook secret not configured.');

        $signatureHeader = $request->header('Stripe-Signature')
            ?: throw new InvalidWebhookSignature('Missing Stripe-Signature header.');

        $payload = $request->getContent();
        if (! $this->verifySignature($payload, $signatureHeader, $secret)) {
            throw new InvalidWebhookSignature('Stripe signature verification failed.');
        }

        $event = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        $object = $event['data']['object'] ?? [];

        return [
            'event_id' => $event['id'],
            'type' => $event['type'],
            'transaction_id' => $object['id'] ?? null,
            'order_reference' => $object['metadata']['order_reference']
                ?? $object['client_reference_id'] ?? null,
            'payload' => $event,
        ];
    }

    private function verifySignature(string $payload, string $header, string $secret): bool
    {
        $parts = collect(explode(',', $header))
            ->mapWithKeys(function ($part) {
                [$k, $v] = explode('=', $part, 2) + [1 => ''];
                return [$k => $v];
            });

        $timestamp = $parts->get('t');
        $signature = $parts->get('v1');

        if (! $timestamp || ! $signature) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return false; // replay window
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return hash_equals($expected, $signature);
    }
}
