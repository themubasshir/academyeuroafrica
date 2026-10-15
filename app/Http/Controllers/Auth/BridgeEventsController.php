<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ExternalIdentity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Server-to-server account-lifecycle events from the Joomla bridge plugin.
 * HMAC-verified like the code-exchange channel; lets Joomla push email changes,
 * disables and deletes so academy accounts stay in sync.
 *
 * Body: {"sub":"…","event":"email_changed|disabled|deleted","email":"…"}
 */
class BridgeEventsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) config('services.joomla.bridge_secret');
        abort_if($secret === '', 503);

        $timestamp = $request->header('X-EAMN-Timestamp', '');
        $signature = $request->header('X-EAMN-Signature', '');
        $body = $request->getContent();

        abort_if($timestamp === '' || $signature === '' || abs(time() - (int) $timestamp) > 300, 401);
        abort_unless(hash_equals(hash_hmac('sha256', $timestamp."\n".$body, $secret), $signature), 401);

        $data = json_decode($body, true) ?: [];
        $sub = $data['sub'] ?? null;
        abort_if($sub === null, 422);

        $identity = ExternalIdentity::where('provider', 'joomla')->where('subject', $sub)->first();
        if ($identity === null) {
            return response()->json(['ok' => true, 'matched' => false]);
        }

        $user = $identity->user;
        $event = $data['event'] ?? '';

        match ($event) {
            'email_changed' => $user->update(['email' => $data['email'] ?? $user->email]),
            'disabled', 'deleted' => $user->update(['status' => 'disabled', 'disabled_at' => now()]),
            default => null,
        };

        AuditLog::record('bridge.event', $user, ['event' => $event, 'sub' => $sub]);

        return response()->json(['ok' => true]);
    }
}
