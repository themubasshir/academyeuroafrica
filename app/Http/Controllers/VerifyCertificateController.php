<?php

namespace App\Http\Controllers;

use App\Models\Certificate;

class VerifyCertificateController extends Controller
{
    /**
     * Public verification — returns only safe credential fields.
     * Never reveals whether a similar ref exists (anti-enumeration via
     * random 32-char refs + throttle + identical 404 for missing/revoked
     * lookups on the ref itself).
     */
    public function show(string $ref)
    {
        // Reject anything that isn't exactly our ref format — fast 404.
        abort_unless(strlen($ref) === 32 && ctype_alnum($ref), 404);

        $cert = Certificate::where('public_ref', $ref)->first();
        abort_unless($cert, 404);

        return view('certificates.verify', [
            'status' => $cert->isRevoked() ? 'revoked' : 'valid',
            'cert' => $cert,
        ]);
    }
}
