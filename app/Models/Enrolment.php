<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'course_variant_id', 'order_id', 'source', 'grant_reason', 'status', 'enrolled_at', 'activated_at', 'entitlement_started_at', 'entitlement_ended_at', 'expires_at'])]
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

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return HasMany<LessonProgress, $this> */
    /** @return HasOne<Certificate, $this> */
    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class)->latestOfMany('issued_at');
    }

    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $expiry = $this->entitlement_ended_at ?? $this->expires_at;

        return $expiry === null || $expiry->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->status === 'active' && ! $this->isActive();
    }

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
            'activated_at' => 'datetime',
            'entitlement_started_at' => 'datetime',
            'entitlement_ended_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
