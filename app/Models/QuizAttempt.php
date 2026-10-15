<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'quiz_id', 'user_id', 'attempt_no', 'started_at', 'submitted_at',
    'score', 'status', 'question_order',
])]
class QuizAttempt extends Model
{
    use HasFactory;

    /** @return BelongsTo<Quiz, $this> */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<QuizAnswer, $this> */
    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }

    public function isExpired(): bool
    {
        $limit = $this->quiz->time_limit_minutes;
        if ($limit === null || $this->status !== 'in_progress') {
            return false;
        }

        return $this->started_at->addMinutes($limit)->isPast();
    }

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'question_order' => 'array',
        ];
    }
}
