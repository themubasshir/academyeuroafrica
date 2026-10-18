<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'programme_id', 'title', 'slug', 'description', 'learning_outcomes',
    'thumbnail_path', 'delivery_types', 'status', 'access_days', 'checkout_variant_id',
    'sort_order',
])]
class Course extends Model
{
    use HasFactory;

    /** @return BelongsTo<Programme, $this> */
    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    /** @return HasMany<CourseVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(CourseVariant::class);
    }

    /** @return BelongsTo<CourseVariant, $this> */
    public function checkoutVariant(): BelongsTo
    {
        return $this->belongsTo(CourseVariant::class, 'checkout_variant_id');
    }

    /** @return HasMany<CourseModule, $this> */
    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class)->orderBy('sort_order');
    }

    /** @return HasMany<TrainerAssignment, $this> */
    public function trainerAssignments(): HasMany
    {
        return $this->hasMany(TrainerAssignment::class);
    }

    /** @return HasManyThrough<Lesson, CourseModule, $this> */
    public function lessons(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(Lesson::class, CourseModule::class);
    }

    /** @return HasMany<Quiz, $this> */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    /** @return HasMany<Assignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    /** @return HasOne<CertificationPolicy, $this> */
    public function certificationPolicy(): HasOne
    {
        return $this->hasOne(CertificationPolicy::class);
    }

    /** @return HasMany<Group, $this> */
    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    /** @return HasMany<LiveSession, $this> */
    public function liveSessions(): HasMany
    {
        return $this->hasMany(LiveSession::class);
    }

    /** @return MorphMany<Material, $this> */
    public function materials(): MorphMany
    {
        return $this->morphMany(Material::class, 'attachable');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    protected function casts(): array
    {
        return [
            'learning_outcomes' => 'array',
            'delivery_types' => 'array',
        ];
    }
}
