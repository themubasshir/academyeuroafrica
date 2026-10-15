<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'course_variant_id', 'order_id', 'status', 'enrolled_at', 'expires_at'])]
class Enrolment extends Model
{
    use HasFactory;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<CourseVariant, $this> */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(CourseVariant::class, 'course_variant_id');
    }

    /** @return HasMany<LessonProgress, $this> */
    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
