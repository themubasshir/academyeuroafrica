<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['enrolment_id', 'kind', 'approver_id'])]
class EnrolmentApproval extends Model
{
    public function enrolment(): BelongsTo
    {
        return $this->belongsTo(Enrolment::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
