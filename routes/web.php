<?php

use App\Http\Controllers\Auth\BridgeConsumeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Deploy\MigrateRunnerController;
use App\Http\Controllers\Webhooks\PaymentWebhookController;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', fn () => view('welcome'))->name('home');

// Public catalogue
Route::livewire('programmes/{programme}', 'catalogue.programme-show')->name('programme.show');
Route::livewire('courses/{course}', 'catalogue.course-show')->name('course.show');

// Local credential login (staff fallback + development).
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Joomla bridge SSO consume endpoint.
Route::get('auth/bridge/consume', BridgeConsumeController::class)
    ->middleware('throttle:10,1')
    ->name('bridge.consume');

// Authenticated areas — every privileged area is behind role middleware AND
// the `active` middleware so disabled accounts are dropped immediately.
Route::middleware(['auth', 'active'])->group(function () {
    Route::livewire('dashboard', 'student.dashboard')
        ->name('student.dashboard');

    Route::get('checkout/success', [CheckoutController::class, 'success'])
        ->name('checkout.success');
    Route::get('checkout/cancelled', [CheckoutController::class, 'cancelled'])
        ->name('checkout.cancelled');
    Route::get('checkout/{variant}', [CheckoutController::class, 'show'])
        ->name('checkout.show');
    Route::post('checkout/{variant}', [CheckoutController::class, 'store'])
        ->name('checkout.store');
});

// One-time deploy migration runner — IP+token gated, self-disabling.
Route::post('deploy/migrate', MigrateRunnerController::class)
    ->middleware('throttle:5,1')
    ->name('deploy.migrate');

// Payment webhooks — no auth, no CSRF, verified per-gateway.
Route::post('webhooks/{gateway}', PaymentWebhookController::class)
    ->name('webhooks.payment');

// Local-only test checkout simulator (never registered in production).
if (app()->environment(['local', 'testing'])) {
    Route::get('test-checkout/{reference}', function (string $reference) {
        $order = Order::where('reference', $reference)->firstOrFail();

        return view('test-checkout', ['order' => $order]);
    })->middleware(['auth', 'active'])->name('test-checkout.show');

    Route::post('test-checkout/{reference}/pay', function (string $reference, Request $request) {
        $order = Order::where('reference', $reference)->firstOrFail();
        abort_unless($order->user_id === $request->user()->id, 403);

        // Fire the same webhook path the real gateway would hit.
        $payload = json_encode([
            'event_id' => 'evt_'.Str::random(16),
            'type' => 'test.paid',
            'transaction_id' => 'test_txn_'.$order->reference,
            'order_reference' => $order->reference,
        ]);
        $secret = config('services.test_gateway.secret', 'test-secret');
        $sig = hash_hmac('sha256', $payload, $secret);

        $resp = Http::withHeaders(['X-Test-Signature' => $sig])
            ->withBody($payload, 'application/json')
            ->post(route('webhooks.payment', 'test'));

        return redirect()->route('checkout.success', ['order' => $order->reference]);
    })->middleware(['auth', 'active'])->name('test-checkout.pay');
}

Route::middleware(['auth', 'active', 'role:trainer,super-admin,academy-admin'])
    ->prefix('trainer')->name('trainer.')->group(function () {
        Route::livewire('dashboard', 'trainer.dashboard')->name('dashboard');
    });

Route::middleware(['auth', 'active', 'role:super-admin,academy-admin'])
    ->prefix('admin')->name('admin.')->group(function () {
        Route::livewire('dashboard', 'admin.dashboard')->name('dashboard');
    });
