<?php

use App\Http\Controllers\Auth\BridgeConsumeController;
use App\Http\Controllers\Auth\BridgeEventsController;
use App\Http\Controllers\Auth\BridgeStartController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Deploy\MigrateRunnerController;
use App\Http\Controllers\Files\MaterialController;
use App\Http\Controllers\VerifyCertificateController;
use App\Http\Controllers\Webhooks\PaymentWebhookController;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

// Private portal: `/` is the entry point, not a marketing page.
// Guests see the branded login; signed-in users are sent to their role home.
Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }
    $user = auth()->user();

    return redirect()->to(match (true) {
        $user->isAdmin() => route('admin.dashboard'),
        $user->isTrainer() => route('trainer.dashboard'),
        default => route('student.dashboard'),
    });
})->name('home');

// Public catalogue
Route::livewire('programmes/{programme}', 'catalogue.programme-show')->name('programme.show');
Route::livewire('courses/{course}', 'catalogue.course-show')->name('course.show');

// Local credential login (staff fallback + development).
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('register.store');
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')->name('verification.send');
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Joomla bridge SSO round-trip.
Route::get('auth/bridge/start', BridgeStartController::class)
    ->middleware('throttle:10,1')
    ->name('bridge.start');
Route::get('auth/bridge/consume', BridgeConsumeController::class)
    ->middleware('throttle:10,1')
    ->name('bridge.consume');
Route::post('auth/bridge/events', BridgeEventsController::class)
    ->middleware('throttle:30,1')
    ->name('bridge.events');

// Authenticated areas — every privileged area is behind role middleware AND
// the `active` middleware so disabled accounts are dropped immediately.
Route::middleware(['auth', 'active'])->group(function () {
    Route::middleware('verified')->group(function () {
        Route::livewire('dashboard', 'student.dashboard')->name('student.dashboard');

        // Student course workspace (authorisation scoped inside components)
        Route::livewire('learn/{enrolment}', 'student.course-show')->name('learn.course');
        Route::livewire('learn/{enrolment}/lesson/{lesson}', 'student.lesson-show')->name('learn.lesson');
        Route::livewire('learn/{enrolment}/assignment/{assignment}', 'student.assignment-show')->name('learn.assignment');
        Route::livewire('learn/{enrolment}/quiz/{quiz}', 'student.quiz-take')->name('learn.quiz');
        Route::livewire('my/results', 'student.results')->name('student.results');
        Route::livewire('my/training', 'student.training')->name('student.training');
        Route::livewire('my/certificates', 'student.certificates')->name('student.certificates');
    });
    Route::livewire('my/profile', 'student.profile')->name('student.profile');
    Route::livewire('my/orders', 'student.orders')->name('student.orders');
    Route::livewire('my/notifications', 'student.notifications')->name('student.notifications');
    Route::get('my/certificates/{certificate}/download', [CertificateController::class, 'download'])
        ->name('student.certificates.download');

    Route::get('checkout/success', [CheckoutController::class, 'success'])
        ->name('checkout.success');
    Route::get('checkout/cancelled', [CheckoutController::class, 'cancelled'])
        ->name('checkout.cancelled');
    // PayPal browser return/cancel — captures server-side before showing status
    Route::get('checkout/paypal/return', [CheckoutController::class, 'paypalReturn'])
        ->name('checkout.paypal.return');
    Route::get('checkout/paypal/cancel', [CheckoutController::class, 'paypalCancel'])
        ->name('checkout.paypal.cancel');
    Route::get('checkout/{variant}', [CheckoutController::class, 'show'])
        ->name('checkout.show');
    Route::post('checkout/{variant}', [CheckoutController::class, 'store'])
        ->name('checkout.store');

    // Authorised private file delivery
    Route::get('files/materials/{material}', [MaterialController::class, 'show'])
        ->name('files.material');
    Route::get('files/attachments/{attachment}', [MaterialController::class, 'attachment'])
        ->name('files.attachment');
});

// One-time deploy migration runner — IP+token gated, self-disabling.
Route::post('deploy/migrate', MigrateRunnerController::class)
    ->middleware('throttle:5,1')
    ->name('deploy.migrate');

// Payment webhooks — no auth, no CSRF, verified per-gateway.
Route::post('webhooks/{gateway}', PaymentWebhookController::class)
    ->name('webhooks.payment');

// Public certificate verification — no auth, rate-limited, non-sequential ref.
Route::get('verify/{ref}', [VerifyCertificateController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('certificates.verify');

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

        Http::withHeaders(['X-Test-Signature' => $sig])
            ->withBody($payload, 'application/json')
            ->post(route('webhooks.payment', 'test'));

        return redirect()->route('checkout.success', ['order' => $order->reference]);
    })->middleware(['auth', 'active'])->name('test-checkout.pay');
}

Route::middleware(['auth', 'active', 'role:trainer,super-admin,academy-admin'])
    ->prefix('trainer')->name('trainer.')->group(function () {
        Route::livewire('dashboard', 'trainer.dashboard')->name('dashboard');
        Route::livewire('courses/{course}/manage', 'trainer.course-manage')->name('course.manage');
        Route::livewire('courses/{course}/sessions', 'trainer.sessions')->name('sessions');
        Route::livewire('courses/{course}/groups', 'trainer.groups')->name('groups');
        Route::livewire('courses/{course}/assignments', 'trainer.assignments')->name('assignments');
        Route::livewire('assignments/{assignment}/grade', 'trainer.grading')->name('grading');
        Route::livewire('courses/{course}/quizzes', 'trainer.quizzes')->name('quizzes');
        Route::livewire('quizzes/{quiz}/edit', 'trainer.quiz-builder')->name('quiz.builder');
        Route::livewire('quizzes/{quiz}/attempts', 'trainer.quiz-attempts')->name('quiz.attempts');
        Route::livewire('courses/{course}/students', 'trainer.students')->name('students');
        Route::livewire('courses/{course}/certifications', 'trainer.certifications')->name('certifications');
        Route::livewire('sessions/{session}/attendance', 'trainer.session-attendance')->name('session.attendance');
        Route::livewire('availability', 'trainer.availability')->name('availability');
    });

Route::middleware(['auth', 'active', 'role:super-admin,academy-admin'])
    ->prefix('admin')->name('admin.')->group(function () {
        Route::livewire('dashboard', 'admin.dashboard')->name('dashboard');
        Route::livewire('programmes', 'admin.programmes')->name('programmes');
        Route::livewire('courses', 'admin.courses')->name('courses');
        Route::livewire('packages', 'admin.variants')->name('variants');
        Route::livewire('users', 'admin.users')->name('users');
        Route::livewire('orders', 'admin.orders')->name('orders');
        Route::livewire('enrolments', 'admin.enrolments')->name('enrolments');
        Route::livewire('materials', 'admin.materials')->name('materials');
        Route::livewire('reports', 'admin.reports')->name('reports');
        Route::livewire('integrations', 'admin.integrations')->name('integrations');
        Route::livewire('audit-logs', 'admin.audit-logs')->name('audit-logs');
        Route::livewire('certificates', 'admin.certificates')->name('certificates');
        Route::livewire('certificates/policy/{course}', 'admin.certificate-policy')->name('certificates.policy');
        Route::livewire('certificates/audit', 'admin.certificate-audit')->name('certificates.audit');
    });
