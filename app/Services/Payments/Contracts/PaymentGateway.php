<?php

namespace App\Services\Payments\Contracts;

use App\Models\Order;
use Illuminate\Http\Request;

/**
 * Gateway contract. Implementations must verify webhook signatures server-side
 * and must never trust browser-supplied success/failure signals for fulfilment.
 */
interface PaymentGateway
{
    /** Machine name, e.g. 'stripe'. */
    public function name(): string;

    /**
     * Create a hosted checkout for the order and return the URL to send the
     * customer to. The order total is authoritative server-side.
     *
     * @return array{redirect_url:string, transaction_id:string}
     */
    public function createCheckout(Order $order): array;

    /**
     * Verify an inbound webhook's signature and return a normalised event.
     *
     * @return array{event_id:string, type:string, transaction_id:?string, order_reference:?string, payload:array}
     *
     * @throws \App\Services\Payments\InvalidWebhookSignature
     */
    public function verifyWebhook(Request $request): array;

    /** Whether this gateway is a real production provider (not the test adapter). */
    public function isLive(): bool;
}
