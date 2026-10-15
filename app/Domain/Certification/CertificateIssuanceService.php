<?php

namespace App\Domain\Certification;

use App\Models\Certificate;
use App\Models\CertificateAudit;
use App\Models\CertificationPolicy;
use App\Models\Enrolment;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Issues certificates idempotently. The `certificates.active_key` unique
 * index (= enrolment_id while the certificate is active, NULL afterwards)
 * makes concurrent issuance safe: the second insert fails and we re-read
 * the winner's record.
 */
class CertificateIssuanceService
{
    public function __construct(private CertificationEligibilityService $eligibility) {}

    /**
     * Evaluate and, when eligible + policy allows it, issue a certificate.
     * Returns the Certificate (new or existing) or the evaluation array.
     *
     * @return Certificate|array{status:string,checks:array,percent:?int}
     */
    public function issueIfEligible(Enrolment $enrolment, ?User $actor = null, bool $force = false): Certificate|array
    {
        $result = $this->eligibility->evaluate($enrolment);

        if ($result['status'] === 'disabled') {
            return $result;
        }

        if ($result['status'] !== 'eligible' && ! $force) {
            return $result;
        }

        $policy = $result['policy'];
        if (! $policy->auto_issue && ! $force) {
            return $result + ['status' => 'eligible']; // awaits manual/admin issue
        }

        return $this->issue($enrolment, $policy, $actor);
    }

    /**
     * Issue regardless of auto_issue flag (admin/trainer manual action).
     * Caller must have already confirmed eligibility or accept forcing.
     */
    public function issue(Enrolment $enrolment, CertificationPolicy $policy, ?User $actor = null): Certificate
    {
        // Active cert already? Return it — never duplicate.
        if ($existing = Certificate::where('enrolment_id', $enrolment->id)->whereNotNull('active_key')->first()) {
            return $existing;
        }

        $user = $enrolment->user;
        $course = $enrolment->variant->course;

        try {
            $cert = DB::transaction(function () use ($enrolment, $user, $course, $policy) {
                return Certificate::create([
                    'uuid' => (string) Str::uuid(),
                    'public_ref' => Str::random(32),
                    'number' => $this->nextNumber(),
                    'user_id' => $user->id,
                    'enrolment_id' => $enrolment->id,
                    'course_id' => $course->id,
                    'course_title' => $course->title,
                    'recipient_name' => $user->name,
                    'org_name' => 'EuroAfrica Media Academy',
                    'signatory_name' => $policy->signatory_name,
                    'signatory_title' => $policy->signatory_title,
                    'issued_at' => now(),
                    'completed_at' => $enrolment->lessonProgress()->whereNotNull('completed_at')->max('completed_at') ?? now(),
                    'template_version' => $policy->template_version,
                    'status' => 'issued',
                    'active_key' => $enrolment->id,
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            // Concurrent issue won — read it back.
            return Certificate::where('enrolment_id', $enrolment->id)->whereNotNull('active_key')->firstOrFail();
        }

        try {
            $this->renderPdf($cert);
        } catch (\Throwable $e) {
            $this->audit($cert, 'failed', 'PDF render: '.$e->getMessage());
        }

        $this->audit($cert, 'issued', $actor ? "by {$actor->email}" : 'automatic');

        return $cert->fresh();
    }

    /**
     * Reissue after revocation: revokes the old record (status → superseded),
     * issues a new cert with a new number under the same enrolment. Audited.
     */
    public function reissue(Certificate $old, User $actor, string $reason): Certificate
    {
        $enrolment = $old->enrolment;
        $policy = $enrolment->variant->course->certificationPolicy
            ?? abort(422, 'Course has no certification policy.');

        $new = DB::transaction(function () use ($old, $enrolment, $policy, $actor, $reason) {
            $old->forceFill([
                'status' => 'superseded',
                'revoked_at' => now(),
                'revoked_by' => $actor->id,
                'revocation_reason' => $reason,
                'active_key' => null,
            ])->save();

            $this->audit($old, 'superseded', $reason);

            $new = Certificate::create([
                'uuid' => (string) Str::uuid(),
                'public_ref' => Str::random(32),
                'number' => $this->nextNumber(),
                'user_id' => $old->user_id,
                'enrolment_id' => $enrolment->id,
                'course_id' => $old->course_id,
                'course_title' => $old->course_title,   // keep original snapshot context
                'recipient_name' => $old->recipient_name,
                'org_name' => $old->org_name,
                'signatory_name' => $policy->signatory_name,
                'signatory_title' => $policy->signatory_title,
                'issued_at' => now(),
                'completed_at' => $old->completed_at,
                'template_version' => $policy->template_version,
                'status' => 'issued',
                'active_key' => $enrolment->id,
            ]);
            $old->forceFill(['superseded_by' => $new->id])->save();

            return $new;
        });

        try {
            $this->renderPdf($new);
        } catch (\Throwable $e) {
            $this->audit($new, 'failed', 'PDF render: '.$e->getMessage());
        }

        $this->audit($new, 'reissued', "by {$actor->email}: {$reason}");

        return $new->fresh();
    }

    /** Revoke — public verification flips to Revoked; PDF already out stays out. */
    public function revoke(Certificate $cert, User $actor, string $reason): Certificate
    {
        abort_if($cert->status !== 'issued', 422, 'Certificate is not active.');

        $cert->forceFill([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revoked_by' => $actor->id,
            'revocation_reason' => $reason,
            'active_key' => null,
        ])->save();

        $this->audit($cert, 'revoked', "by {$actor->email}: {$reason}");

        return $cert;
    }

    /** EAMN-YYYY-##### — sequential per year, safe inside transaction. */
    private function nextNumber(): string
    {
        $year = now()->year;
        $last = Certificate::where('number', 'like', "EAMN-{$year}-%")
            ->lockForUpdate()->orderByDesc('number')->value('number');
        $seq = $last ? ((int) substr($last, -5)) + 1 : 1;

        return sprintf('EAMN-%d-%05d', $year, $seq);
    }

    /** Render + store the PDF privately, update pdf_path. */
    public function renderPdf(Certificate $cert): void
    {
        $qrPng = (new Builder(
            writer: new PngWriter,
            data: $cert->verificationUrl(),
            encoding: new Encoding('UTF-8'),
            size: 120,
            margin: 0,
        ))->build();

        $logo = public_path('brand/eman-logo.png');
        abort_unless(file_exists($logo), 500, 'Brand logo missing.');

        $pdf = Pdf::loadView('certificates.pdf', [
            'cert' => $cert,
            'qrDataUri' => 'data:image/png;base64,'.base64_encode($qrPng->getString()),
            'logoDataUri' => 'data:image/png;base64,'.base64_encode(file_get_contents($logo)),
        ])->setPaper('a4', 'landscape');

        $path = "certificates/{$cert->uuid}.pdf";
        Storage::disk('local')->put($path, $pdf->output());

        $cert->forceFill(['pdf_path' => $path, 'pdf_disk' => 'local'])->save();
    }

    public function audit(?Certificate $cert, string $action, ?string $detail = null, ?int $enrolmentId = null): void
    {
        CertificateAudit::create([
            'certificate_id' => $cert?->id,
            'enrolment_id' => $enrolmentId ?? $cert?->enrolment_id,
            'actor_id' => auth()->id(),
            'action' => $action,
            'detail' => $detail ? Str::limit($detail, 250) : null,
        ]);
    }
}
