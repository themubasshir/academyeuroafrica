<?php

namespace App\Http\Controllers;

use App\Models\Certificate;

class CertificateController extends Controller
{
    /**
     * Download the student's own certificate PDF. Admin bypasses via
     * Gate::before. Trainers do not download student certs here.
     */
    public function download(Certificate $certificate)
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() || $certificate->user_id === $user->id, 403);

        return $certificate->downloadPdf();
    }
}
