<?php

namespace App\Livewire\Student;

use App\Models\Assignment;
use App\Models\LiveSession;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();
        $enrolments = $user->enrolments()
            ->with('variant.course.programme')
            ->latest('enrolled_at')
            ->get();

        $courseIds = $enrolments->pluck('variant.course_id');

        return view('livewire.student.dashboard', [
            'enrolments' => $enrolments,
            'upcoming' => LiveSession::whereIn('course_id', $courseIds)
                ->where('status', 'scheduled')->where('starts_at', '>', now())
                ->orderBy('starts_at')->get()
                ->filter(fn ($s) => $s->visibleTo($user))->take(5),
            'due' => Assignment::whereIn('course_id', $courseIds)
                ->where('status', 'published')->where('due_at', '>', now())
                ->orderBy('due_at')->get()
                ->filter(fn ($a) => $a->isRecipient($user))->take(5),
        ])->layout('layouts::app', ['title' => 'My Courses']);
    }
}
