<?php

namespace App\Livewire\Student;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrolment;
use App\Models\Lesson;
use App\Models\LiveSession;
use App\Models\Material;
use App\Models\Quiz;
use Livewire\Attributes\Url;
use Livewire\Component;

class CourseShow extends Component
{
    public Enrolment $enrolment;

    #[Url]
    public string $tab = 'overview';

    public function mount(Enrolment $enrolment): void
    {
        $this->authorize('view', $enrolment);
    }

    /** All materials on the course tree (course → module → lesson) visible to this student. */
    private function visibleMaterials()
    {
        $course = $this->enrolment->variant->course;
        $user = auth()->user();
        $moduleIds = $course->modules()->pluck('id');
        $lessonIds = Lesson::whereIn('course_module_id', $moduleIds)->pluck('id');

        return Material::with('group')
            ->where(function ($q) use ($course, $moduleIds, $lessonIds) {
                $q->where(fn ($x) => $x->where('attachable_type', Course::class)->where('attachable_id', $course->id))
                    ->orWhere(fn ($x) => $x->where('attachable_type', CourseModule::class)->whereIn('attachable_id', $moduleIds))
                    ->orWhere(fn ($x) => $x->where('attachable_type', Lesson::class)->whereIn('attachable_id', $lessonIds));
            })
            ->get()
            ->filter(fn (Material $m) => $m->visibleTo($user))
            ->sortByDesc('created_at')->values();
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
                ->where('status', 'published')->withCount('questions')->get(),
            'materials' => $this->visibleMaterials(),
        ])->layout('layouts::app', ['title' => $course->title]);
    }
}
