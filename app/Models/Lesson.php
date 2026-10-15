<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['course_module_id', 'title', 'content', 'type', 'sort_order', 'status', 'requires_previous'])]
class Lesson extends Model
{
    use HasFactory;

    /** @return BelongsTo<CourseModule, $this> */
    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'course_module_id');
    }

    /** @return HasOne<CourseVideo, $this> */
    public function video(): HasOne
    {
        return $this->hasOne(CourseVideo::class);
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
        return ['requires_previous' => 'boolean'];
    }
}
