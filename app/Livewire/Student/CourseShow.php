<?php

namespace App\Livewire\Student;

use App\Models\Assignment;
use App\Models\Enrolment;
use App\Models\LiveSession;
use App\Models\Quiz;
use Livewire\Component;

class CourseShow extends Component
{
    public Enrolment $enrolment;

    public function mount(Enrolment $enrolment): void
    {
        $this->authorize('view', $enrolment);
    }

    public function render()
    {
        $course = $this->enrolment->variant->course;

        return view('livewire.student.course-show', [
            'course' => $course,
            'modules' => $course->modules()->where('status', 'published')
                ->with(['lessons' => fn ($q) => $q->where('status', 'published')->with('video')])
                ->get(),
            'progress' => $this->enrolment->lessonProgress()->pluck('completed_at', 'lesson_id'),
            'sessions' => LiveSession::where('course_id', $course->id)
                ->where('status', 'scheduled')->where('starts_at', '>', now())
                ->orderBy('starts_at')->get()
                ->filter(fn ($s) => $s->visibleTo(auth()->user())),
            'assignments' => Assignment::where('course_id', $course->id)
                ->where('status', 'published')->orderBy('due_at')->get()
                ->filter(fn ($a) => $a->isRecipient(auth()->user())),
            'quizzes' => Quiz::where('course_id', $course->id)
                ->where('status', 'published')->get(),
            'materials' => $course->materials()->get(),
        ])->layout('layouts::app', ['title' => $course->title]);
    }
}
