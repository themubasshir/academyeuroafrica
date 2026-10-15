<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Fillable([
    'uuid', 'public_ref', 'number', 'user_id', 'enrolment_id', 'course_id',
    'course_title', 'recipient_name', 'org_name', 'signatory_name', 'signatory_title',
    'issued_at', 'completed_at', 'template_version', 'status',
    'revoked_at', 'revoked_by', 'revocation_reason', 'superseded_by',
    'pdf_disk', 'pdf_path', 'active_key', 'meta',
])]
class Certificate extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enrolment(): BelongsTo
    {
        return $this->belongsTo(Enrolment::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function superseding(): BelongsTo
    {
        return $this->belongsTo(Certificate::class, 'superseded_by');
    }

    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }

    /** Public verification URL — QR + shared link point here. */
    public function verificationUrl(): string
    {
        return config('app.academy_url').'/verify/'.$this->public_ref;
    }

    /** Download the stored PDF via the private disk. */
    public function downloadPdf(): StreamedResponse
    {
        abort_unless($this->pdf_path && Storage::disk($this->pdf_disk)->exists($this->pdf_path), 404);

        return Storage::disk($this->pdf_disk)->download(
            $this->pdf_path,
            'certificate-'.$this->number.'.pdf'
        );
    }

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'completed_at' => 'datetime',
            'revoked_at' => 'datetime',
            'meta' => 'array',
        ];
    }
}
