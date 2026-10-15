<?php

namespace App\Policies;

use App\Models\AssignmentSubmission;
use App\Models\TrainerAssignment;
use App\Models\User;

class AssignmentSubmissionPolicy
{
    public function view(User $user, AssignmentSubmission $submission): bool
    {
        if ($submission->user_id === $user->id) {
            return true;
        }

        if ($user->hasRole('academy-admin')) {
            return true;
        }

        return $user->isTrainer() && TrainerAssignment::where('trainer_id', $user->id)
            ->where('course_id', $submission->assignment->course_id)->exists();
    }

    public function grade(User $user, AssignmentSubmission $submission): bool
    {
        if ($user->hasRole('academy-admin')) {
            return true;
        }

        return $user->isTrainer() && TrainerAssignment::where('trainer_id', $user->id)
            ->where('course_id', $submission->assignment->course_id)->exists();
    }
}
