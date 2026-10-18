<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'course_id', 'name', 'description', 'delivery_type', 'price_cents', 'currency',
    'availability', 'capacity', 'status', 'source_key', 'approval_status', 'duration',
    'inclusions', 'eligibility', 'price_source', 'checkout_ref', 'expiry_policy',
    'access_duration_days', 'fixed_expiry_at', 'renewable', 'display_order',
])]
class CourseVariant extends Model
{
    use HasFactory;

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return HasMany<Enrolment, $this> */
    public function enrolments(): HasMany
    {
        return $this->hasMany(Enrolment::class);
    }

    public function priceFormatted(): string
    {
        return match ($this->availability) {
            'free' => 'Free',
            'quote' => 'Price on application',
            default => $this->price_cents === null
                ? 'Price on application'
                : number_format($this->price_cents / 100, 2).' '.$this->currency,
        };
    }

    public function isAvailableForEnrollment(): bool
    {
        return in_array($this->availability ?? 'paid', ['free', 'paid'], true)
            && $this->status === 'published'
            && $this->approval_status === 'approved'
            && $this->course->isPublished();
    }

    public function isPurchasable(): bool
    {
        return ($this->availability ?? 'paid') === 'paid'
            && $this->status === 'published'
            && $this->approval_status === 'approved'
            && $this->price_cents !== null
            && $this->course->isPublished();
    }

    public function expiryFrom(\DateTimeInterface $activation): ?\DateTimeInterface
    {
        if ($this->expiry_policy === 'duration' && (int) $this->access_duration_days < 1) {
            return null;
        }

        return match ($this->expiry_policy) {
            'duration' => now()->setTimestamp($activation->getTimestamp())->addDays((int) $this->access_duration_days),
            'fixed' => $this->fixed_expiry_at,
            default => null,
        };
    }

    /**
     * ponytail: fixed-policy renewals cannot extend past the configured date;
     * admin must update fixed_expiry_at first. Upgrade: rolling fixed windows.
     */
    public function renewalExpiry(?\DateTimeInterface $currentEnd, \DateTimeInterface $activation): ?\DateTimeInterface
    {
        $base = $currentEnd?->isFuture() ? $currentEnd : $activation;

        return match ($this->expiry_policy) {
            'duration' => $base->copy()->addDays((int) $this->access_duration_days),
            'fixed' => $this->fixed_expiry_at?->isAfter($base) ? $this->fixed_expiry_at : $base,
            default => null,
        };
    }

    protected function casts(): array
    {
        return [
            'inclusions' => 'array',
            'eligibility' => 'array',
            'fixed_expiry_at' => 'datetime',
            'renewable' => 'boolean',
        ];
    }
}
