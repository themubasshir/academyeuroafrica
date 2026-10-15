<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course_id', 'name', 'delivery_type', 'price_cents', 'currency', 'capacity', 'status'])]
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

    public function isPurchasable(): bool
    {
        return $this->status === 'published' && $this->course->isPublished();
    }

    public function priceFormatted(): string
    {
        return number_format($this->price_cents / 100, 2).' '.$this->currency;
    }
}
