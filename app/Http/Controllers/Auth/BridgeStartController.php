<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CourseVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Initiates the Joomla SSO round-trip.
 *
 * The academy generates the `state` nonce here and stores it in the session,
 * then sends the user to the Joomla bridge `go` task carrying that state.
 * Joomla binds the issued auth-code to the same state; on consume we verify
 * the returned state matches this session — preventing CSRF and account
 * substitution.
 */
class BridgeStartController extends Controller
{
    private const ALLOWED_TARGETS = [
        '/dashboard' => 'student.dashboard',
        '/trainer/dashboard' => 'trainer.dashboard',
        '/admin/dashboard' => 'admin.dashboard',
    ];

    public function __invoke(Request $request): RedirectResponse
    {
        $state = hash('sha256', Str::random(40).$request->session()->getId());
        $request->session()->put('bridge_state', $state);
        $request->session()->put('bridge_state_expires_at', now()->addMinutes(10)->timestamp);

        $intent = $request->session()->get('bridge_intent');
        if (is_array($intent) && ($intent['expires_at'] ?? 0) >= now()->timestamp) {
            $variant = CourseVariant::with('course')->find((int) ($intent['variant_id'] ?? 0));
            if ($variant?->isPurchasable()) {
                $request->session()->put('url.intended', route('checkout.show', $variant));
            } else {
                $request->session()->forget(['bridge_intent', 'url.intended']);
            }
        } else {
            $request->session()->forget(['bridge_intent', 'url.intended']);
        }

        $target = $request->string('target')->toString();
        if (! $request->session()->has('url.intended') && array_key_exists($target, self::ALLOWED_TARGETS)) {
            $request->session()->put('url.intended', route(self::ALLOWED_TARGETS[$target]));
        }

        $base = rtrim((string) config('services.joomla.base_url'), '/');
        $query = http_build_query([
            'option' => 'com_eamnbridge',
            'task' => 'go',
            'state' => $state,
        ]);

        return redirect()->away($base.'/index.php?'.$query);
    }
}
