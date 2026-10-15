<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['quiz_id', 'type', 'prompt', 'points', 'sort_order'])]
class QuizQuestion extends Model
{
    use HasFactory;

    public const TYPE_MCQ = 'mcq';
    public const TYPE_MULTI = 'multi';
    public const TYPE_TF = 'tf';
    public const TYPE_SHORT = 'short';
    public const TYPE_ESSAY = 'essay';

    public const AUTO_GRADED = [self::TYPE_MCQ, self::TYPE_MULTI, self::TYPE_TF, self::TYPE_SHORT];

    public function isAutoGraded(): bool
    {
        return in_array($this->type, self::AUTO_GRADED, true);
    }

    /** @return BelongsTo<Quiz, $this> */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /** @return HasMany<QuizOption, $this> */
    public function options(): HasMany
    {
        return $this->hasMany(QuizOption::class)->orderBy('sort_order');
    }
}
