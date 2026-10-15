<?php

namespace App\Jobs;

use App\Domain\Certification\CertificateIssuanceService;
use App\Models\CertificateAudit;
use App\Models\Enrolment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Evaluate one enrolment and issue its certificate when eligible.
 * Idempotent — CertificateIssuanceService returns the existing cert on
 * repeat delivery; `active_key` unique index stops concurrent dupes.
 * Runs on the `database` queue → works with `queue:work` cron on shared
 * hosting. Tries 3 times, then logs to certificate_audits via failed().
 */
class MaybeIssueCertificate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $enrolmentId) {}

    public function handle(CertificateIssuanceService $service): void
    {
        $enrolment = Enrolment::with('variant.course', 'user')->find($this->enrolmentId);
        if (! $enrolment) {
            return;
        }

        $service->issueIfEligible($enrolment);
    }

    public function failed(\Throwable $e): void
    {
        CertificateAudit::create([
            'enrolment_id' => $this->enrolmentId,
            'action' => 'failed',
            'detail' => 'job error: '.Str::limit($e->getMessage(), 200),
        ]);
    }
}
