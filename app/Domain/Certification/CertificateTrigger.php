<?php

namespace App\Domain\Certification;

use App\Jobs\MaybeIssueCertificate;
use App\Models\Enrolment;

/**
 * Fires the issuance check after trusted domain events (lesson completion,
 * quiz grading, assignment release, attendance, approval). It runs
 * synchronously so shared hosting does not depend on a permanent queue worker.
 */
class CertificateTrigger
{
    public static function for(Enrolment $enrolment): void
    {
        MaybeIssueCertificate::dispatchSync($enrolment->id);
    }
}
