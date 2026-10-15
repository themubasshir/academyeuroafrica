<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['enrolment_id', 'lesson_id', 'completed_at', 'position_seconds'])]
class LessonProgress extends Model
{
    use HasFactory;

    /** @return BelongsTo<Enrolment, $this> */
    public function enrolment(): BelongsTo
    {
        return $this->belongsTo(Enrolment::class);
    }

    /** @return BelongsTo<Lesson, $this> */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }
}
