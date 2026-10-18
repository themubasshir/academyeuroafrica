<?php

namespace App\Http\Controllers\Webhooks;

use App\Domain\Checkout\OrderService;
use App\Http\Controllers\Controller;
use App\Services\Payments\InvalidWebhookSignature;
use App\Services\Payments\PaymentManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private PaymentManager $payments,
        private OrderService $orders,
    ) {}

    public function __invoke(Request $request, string $gateway): JsonResponse
    {
        // Disabling new checkouts must not suppress refunds or disputes for
        // payments that PayPal already processed.
        $driver = $this->payments->driver($gateway, requireEnabled: false);

        try {
            $event = $driver->verifyWebhook($request);
        } catch (InvalidWebhookSignature $e) {
            report($e);

            return response()->json(['error' => 'invalid signature'], 400);
        }

        $this->orders->fulfil($driver->name(), $event);

        return response()->json(['received' => true]);
    }
}
