<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\TrainerAssignment;
use App\Models\User;

class CoursePolicy
{
    public function view(?User $user, Course $course): bool
    {
        if ($course->isPublished()) {
            return true;
        }

        if (! $user) {
            return false;
        }

        // Unpublished courses visible only to assigned trainers and admins.
        if ($user->hasRole('academy-admin')) {
            return true;
        }

        return $user->isTrainer()
            && TrainerAssignment::where('trainer_id', $user->id)
                ->where('course_id', $course->id)->exists();
    }

    public function manageContent(User $user, Course $course): bool
    {
        if ($user->hasRole('academy-admin')) {
            return true;
        }

        return $user->isTrainer()
            && TrainerAssignment::where('trainer_id', $user->id)
                ->where('course_id', $course->id)->exists();
    }
}
