<?php

namespace App\Livewire\Trainer;

use App\Models\AssignmentSubmission;
use App\Models\Course;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();
        $courseIds = $user->trainerAssignments()->pluck('course_id');
        $courses = Course::whereIn('id', $courseIds)->with('programme')->get();

        return view('livewire.trainer.dashboard', [
            'courses' => $courses,
            'pendingGrading' => AssignmentSubmission::where('status', 'submitted')
                ->whereHas('assignment', fn ($q) => $q->whereIn('course_id', $courseIds))->count(),
        ])->layout('layouts::app', ['title' => 'Trainer Dashboard']);
    }
}
