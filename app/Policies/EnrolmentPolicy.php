<?php

namespace App\Policies;

use App\Models\Enrolment;
use App\Models\TrainerAssignment;
use App\Models\User;

class EnrolmentPolicy
{
    /**
     * A student sees only their own enrolment. A trainer sees enrolments on
     * courses they are assigned to. Admins pass via Gate::before.
     */
    public function view(User $user, Enrolment $enrolment): bool
    {
        if ($enrolment->user_id === $user->id) {
            return $enrolment->isActive();
        }

        if ($user->hasRole('academy-admin')) {
            return true;
        }

        if ($user->isTrainer()) {
            $courseId = $enrolment->variant->course_id;

            return TrainerAssignment::where('trainer_id', $user->id)
                ->where('course_id', $courseId)
                ->exists();
        }

        return false;
    }
}
