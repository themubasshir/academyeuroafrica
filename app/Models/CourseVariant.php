<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'course_id', 'name', 'delivery_type', 'price_cents', 'currency', 'capacity',
    'status', 'source_key', 'approval_status', 'duration', 'inclusions',
    'price_source', 'checkout_ref',
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
        return $this->price_cents === null
            ? 'Price on application'
            : number_format($this->price_cents / 100, 2).' '.$this->currency;
    }

    /** Purchasable only when approved AND published. */
    public function isPurchasable(): bool
    {
        return $this->status === 'published'
            && $this->approval_status === 'approved'
            && $this->price_cents !== null
            && $this->course->isPublished();
    }

    protected function casts(): array
    {
        return ['inclusions' => 'array'];
    }
}
