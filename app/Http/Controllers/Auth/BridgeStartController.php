<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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
    public function __invoke(Request $request): RedirectResponse
    {
        $state = hash('sha256', Str::random(40).$request->session()->getId());
        $request->session()->put('bridge_state', $state);

        // Remember where to land after login (validated same-site path only).
        $target = $request->string('target', '');
        if ($target !== '' && str_starts_with($target->toString(), '/') && ! str_starts_with($target->toString(), '//')) {
            $request->session()->put('url.intended', url($target));
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
