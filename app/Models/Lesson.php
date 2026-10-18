<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['course_module_id', 'title', 'content', 'type', 'sort_order', 'source_key', 'status', 'requires_previous', 'is_required', 'requires_acknowledgement', 'completion_threshold', 'primary_material_id'])]
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

    /** @return BelongsTo<Material, $this> */
    public function primaryMaterial(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'primary_material_id');
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

    public function sanitisedContent(): string
    {
        $content = strip_tags((string) $this->content, '<p><br><strong><em><ul><ol><li><h2><h3><blockquote><code>');

        return (string) preg_replace('/<([a-z][a-z0-9]*)\b[^>]*>/i', '<$1>', $content);
    }

    protected function casts(): array
    {
        return [
            'requires_previous' => 'boolean',
            'is_required' => 'boolean',
            'requires_acknowledgement' => 'boolean',
            'completion_threshold' => 'integer',
        ];
    }
}
