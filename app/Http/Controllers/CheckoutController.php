<?php

namespace App\Http\Controllers;

use App\Domain\Checkout\OrderService;
use App\Models\CourseVariant;
use App\Models\Order;
use App\Services\Payments\PaymentManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private OrderService $orders,
        private PaymentManager $payments,
    ) {}

    public function show(CourseVariant $variant): View
    {
        abort_unless($variant->isPurchasable(), 404);

        return view('checkout.show', [
            'variant' => $variant->load('course'),
            'gateway' => config('services.payments.default', 'stripe'),
        ]);
    }

    public function store(Request $request, CourseVariant $variant): RedirectResponse
    {
        abort_unless($variant->isPurchasable(), 404);

        $order = $this->orders->create($request->user(), [$variant->id]);
        $gateway = $this->payments->driver($request->string('gateway', 'stripe'));
        $result = $this->orders->beginPayment($order, $gateway);

        return redirect()->away($result['redirect_url']);
    }

    /**
     * Browser return URL — display only. Access is granted by the verified
     * webhook, never by reaching this page.
     */
    public function success(Request $request): View
    {
        $order = null;
        if ($ref = $request->query('order')) {
            $order = Order::where('reference', $ref)->where('user_id', $request->user()->id)->first();
        }

        return view('checkout.success', ['order' => $order]);
    }

    public function cancelled(): View
    {
        return view('checkout.cancelled');
    }
}
