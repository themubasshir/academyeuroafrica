<?php

namespace App\Livewire\Student;

use App\Domain\Certification\CertificateTrigger;
use App\Models\Enrolment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use Livewire\Component;

class LessonShow extends Component
{
    public Enrolment $enrolment;

    public Lesson $lesson;

    public int $positionSeconds = 0;

    public function mount(Enrolment $enrolment, Lesson $lesson): void
    {
        $this->authorize('view', $enrolment);
        abort_unless($lesson->module->course_id === $enrolment->variant->course_id, 404);
        abort_unless($lesson->isPublished(), 404);

        $this->positionSeconds = LessonProgress::where('enrolment_id', $enrolment->id)
            ->where('lesson_id', $lesson->id)->value('position_seconds') ?? 0;
    }

    public function savePosition(int $seconds): void
    {
        LessonProgress::updateOrCreate(
            ['enrolment_id' => $this->enrolment->id, 'lesson_id' => $this->lesson->id],
            ['position_seconds' => max(0, $seconds)]
        );
    }

    public function markComplete(): void
    {
        LessonProgress::updateOrCreate(
            ['enrolment_id' => $this->enrolment->id, 'lesson_id' => $this->lesson->id],
            ['completed_at' => now()]
        );
        $this->dispatch('lesson-completed');
        CertificateTrigger::for($this->enrolment);
    }

    public function render()
    {
        return view('livewire.student.lesson-show', [
            'video' => $this->lesson->video,
            'materials' => $this->lesson->materials()->get(),
        ])->layout('layouts::app', ['title' => $this->lesson->title]);
    }
}
