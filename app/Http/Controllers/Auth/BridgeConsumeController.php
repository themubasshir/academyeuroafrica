<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Joomla\BridgeAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * Landing endpoint for the Joomla bridge SSO redirect.
 *
 * The Joomla plugin redirects here with a single-use authorisation code and
 * the `state` nonce the academy placed in the user's session when it sent
 * them to Joomla (or that Joomla set when initiating login). We verify
 * state, exchange the code server-to-server, then log the resolved user in.
 */
class BridgeConsumeController extends Controller
{
    public function __invoke(Request $request, BridgeAuth $bridge): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:64'],
            'state' => ['required', 'string', 'size:64'],
        ]);

        // CSRF/account-substitution guard: state must match this browser session.
        abort_unless(
            hash_equals((string) $request->session()->get('bridge_state', ''), $request->string('state')),
            403,
            'Invalid bridge state.'
        );
        $request->session()->forget('bridge_state');

        try {
            $user = $bridge->consumeCode($request->string('code'));
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'identity-conflict') {
                return redirect()->route('login')->withErrors([
                    'email' => 'An account with this email already exists. Contact the academy to link your account.',
                ]);
            }
            throw $e;
        }

        if (! $user->isActive()) {
            return redirect()->route('login')->withErrors([
                'email' => 'This account has been disabled.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(match (true) {
            $user->isAdmin() => route('admin.dashboard'),
            $user->isTrainer() => route('trainer.dashboard'),
            default => route('student.dashboard'),
        });
    }
}
