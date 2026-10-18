<?php

namespace App\Http\Controllers;

use App\Domain\Checkout\OrderService;
use App\Models\AuditLog;
use App\Models\Course;
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
            'gateway' => config('services.payments.default', 'paypal'),
        ]);
    }

    public function course(Request $request, Course $course): RedirectResponse
    {
        $variant = $course->checkoutVariant()->with('course')->first();

        abort_unless(
            $course->isPublished()
                && $variant !== null
                && $variant->course_id === $course->id
                && $variant->isPurchasable(),
            404
        );

        if ($request->user() === null) {
            $request->session()->put('bridge_intent', [
                'variant_id' => $variant->id,
                'course_id' => $course->id,
                'expires_at' => now()->addMinutes(10)->timestamp,
            ]);
            $request->session()->put('url.intended', route('checkout.course', ['course' => $course->slug]));

            return redirect()->route('login');
        }

        return redirect()->route('checkout.show', $variant);
    }

    public function store(Request $request, CourseVariant $variant): RedirectResponse
    {
        abort_unless($variant->isPurchasable(), 404);

        $order = $this->orders->create($request->user(), [$variant->id]);
        $gateway = $this->payments->driver($request->string('gateway', 'paypal'));
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

    /**
     * PayPal return URL (?token={paypal_order_id}). The student is bounced here
     * after approving at PayPal — we capture the order server-side and fulfil
     * idempotently. Reaching this page alone never grants access.
     */
    public function paypalReturn(Request $request): RedirectResponse|View
    {
        $paypalOrderId = (string) $request->query('token');
        abort_unless($paypalOrderId !== '', 404);

        $order = Order::whereHas('transactions', fn ($q) => $q->where('gateway', 'paypal')
            ->where('gateway_transaction_id', $paypalOrderId))
            ->firstOrFail();

        // Owner-only: the signed-in buyer must own the order.
        abort_unless($order->user_id === $request->user()->id, 403);

        if (! $order->isPaid()) {
            try {
                $event = $this->payments->driver('paypal')->captureOrder($paypalOrderId, $order);
                $this->orders->fulfil('paypal', $event);
            } catch (\RuntimeException $e) {
                report($e);

                return view('checkout.paypal-status', [
                    'order' => $order,
                    'state' => 'pending',
                    'message' => 'Your payment is being confirmed. Access is granted automatically once PayPal completes the capture.',
                ]);
            }
        }

        return redirect()->route('checkout.success', ['order' => $order->reference]);
    }

    /**
     * PayPal cancel URL — leaves the order pending, no enrolment.
     */
    public function paypalCancel(Request $request): View
    {
        $order = null;
        if ($token = $request->query('token')) {
            $order = Order::whereHas('transactions', fn ($query) => $query
                ->where('gateway', 'paypal')
                ->where('gateway_transaction_id', $token))
                ->where('user_id', $request->user()->id)
                ->first();

            if ($order !== null && $order->status === 'pending') {
                $order->update(['status' => 'cancelled']);
                $order->transactions()
                    ->where('gateway', 'paypal')
                    ->where('gateway_transaction_id', $token)
                    ->update(['status' => 'cancelled']);
                AuditLog::record('order.cancelled', $order, ['gateway' => 'paypal']);
            }
        }

        return view('checkout.cancelled', ['order' => $order]);
    }
}
