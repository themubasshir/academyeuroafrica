<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['assignment_id', 'user_id', 'body', 'submitted_at', 'status'])]
class AssignmentSubmission extends Model
{
    use HasFactory;

    /** @return BelongsTo<Assignment, $this> */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<AssignmentAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(AssignmentAttachment::class);
    }

    /** @return HasOne<AssignmentGrade, $this> */
    public function grade(): HasOne
    {
        return $this->hasOne(AssignmentGrade::class);
    }

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }
}
