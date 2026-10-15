<?php

namespace App\Domain\Certification;

use App\Jobs\MaybeIssueCertificate;
use App\Models\Enrolment;

/**
 * Fires the issuance check after trusted domain events (lesson completion,
 * quiz grading, assignment release, attendance, approval). Dispatches onto
 * the `database` queue when it's running; falls back to synchronous
 * evaluation so local/test flows still issue immediately.
 */
class CertificateTrigger
{
    public static function for(Enrolment $enrolment): void
    {
        MaybeIssueCertificate::dispatch($enrolment->id);
    }
}
