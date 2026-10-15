<?php

namespace App\Policies;

use App\Models\QuizAttempt;
use App\Models\TrainerAssignment;
use App\Models\User;

class QuizAttemptPolicy
{
    public function view(User $user, QuizAttempt $attempt): bool
    {
        if ($attempt->user_id === $user->id) {
            return true;
        }

        if ($user->hasRole('academy-admin')) {
            return true;
        }

        return $user->isTrainer() && TrainerAssignment::where('trainer_id', $user->id)
            ->where('course_id', $attempt->quiz->course_id)->exists();
    }
}
