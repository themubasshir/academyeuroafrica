<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['certificate_id', 'enrolment_id', 'actor_id', 'action', 'detail', 'created_at'])]
class CertificateAudit extends Model
{
    public const UPDATED_AT = null;

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
