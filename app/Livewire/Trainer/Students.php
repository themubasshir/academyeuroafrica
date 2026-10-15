<?php

namespace App\Livewire\Trainer;

use App\Models\Course;
use App\Models\Enrolment;
use Livewire\Component;

class Students extends Component
{
    public Course $course;

    public function mount(Course $course): void
    {
        $this->authorize('manageContent', $course);
    }

    public function render()
    {
        $enrolments = Enrolment::whereHas('variant', fn ($q) => $q->where('course_id', $this->course->id))
            ->where('status', 'active')->with(['user', 'lessonProgress'])->get();

        $lessonCount = $this->course->modules()->withCount('lessons')->get()->sum('lessons_count') ?: 1;

        return view('livewire.trainer.students', [
            'rows' => $enrolments->map(fn ($e) => [
                'user' => $e->user,
                'enrolled_at' => $e->enrolled_at,
                'done' => $e->lessonProgress->whereNotNull('completed_at')->count(),
                'total' => $lessonCount,
            ]),
        ])->layout('layouts::app', ['title' => 'Students: '.$this->course->title]);
    }
}
