<?php

namespace App\Livewire\Student;

use App\Domain\Certification\CertificateTrigger;
use App\Models\Enrolment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class LessonShow extends Component
{
    public Enrolment $enrolment;

    public Lesson $lesson;

    public int $positionSeconds = 0;

    public int $progressPercent = 0;

    public bool $completed = false;

    public function mount(Enrolment $enrolment, Lesson $lesson): void
    {
        $this->authorize('view', $enrolment);
        abort_unless($enrolment->isActive(), 403, 'This enrolment has expired.');
        abort_unless($lesson->module->course_id === $enrolment->variant->course_id, 404);
        abort_unless($lesson->isPublished(), 404);

        $progress = LessonProgress::where('enrolment_id', $enrolment->id)
            ->where('lesson_id', $lesson->id)->first();
        $this->positionSeconds = $progress?->position_seconds ?? 0;
        $this->progressPercent = $progress?->progress_percent ?? 0;
        $this->completed = $progress?->completed_at !== null;
    }

    public function savePosition(int $seconds): void
    {
        abort_unless($this->enrolment->isActive(), 403);
        abort_unless($this->lesson->type === 'video', 422);

        $duration = $this->lesson->video?->duration_seconds;
        abort_unless($duration !== null && $duration > 0, 422, 'Video duration is not configured.');

        $completedNow = false;
        DB::transaction(function () use ($seconds, $duration, &$completedNow) {
            $progress = LessonProgress::where('enrolment_id', $this->enrolment->id)
                ->where('lesson_id', $this->lesson->id)
                ->lockForUpdate()
                ->first();
            $position = min($duration, max($progress?->position_seconds ?? 0, max(0, $seconds)));
            $percent = min(100, (int) floor($position / $duration * 100));
            $completedAt = $progress?->completed_at;
            if ($completedAt === null && $percent >= $this->lesson->completion_threshold) {
                $completedAt = now();
                $completedNow = true;
            }

            $progress = LessonProgress::updateOrCreate(
                ['enrolment_id' => $this->enrolment->id, 'lesson_id' => $this->lesson->id],
                [
                    'position_seconds' => $position,
                    'progress_percent' => $percent,
                    'completed_at' => $completedAt,
                ]
            );

            $this->positionSeconds = $progress->position_seconds;
            $this->progressPercent = $progress->progress_percent;
            $this->completed = $progress->completed_at !== null;
        });

        if ($completedNow) {
            CertificateTrigger::for($this->enrolment);
        }
    }

    public function markComplete(): void
    {
        abort_unless($this->enrolment->isActive(), 403);
        if ($this->lesson->type === 'video') {
            abort_unless($this->progressPercent >= $this->lesson->completion_threshold, 422, 'Watch the configured portion of the video first.');
        }

        LessonProgress::updateOrCreate(
            ['enrolment_id' => $this->enrolment->id, 'lesson_id' => $this->lesson->id],
            [
                'completed_at' => now(),
                'progress_percent' => 100,
                'acknowledged_at' => $this->lesson->type === 'pdf' ? now() : null,
            ]
        );
        $this->progressPercent = 100;
        $this->completed = true;
        $this->dispatch('lesson-completed');
        CertificateTrigger::for($this->enrolment);
    }

    public function render()
    {
        return view('livewire.student.lesson-show', [
            'video' => $this->lesson->video,
            'primaryMaterial' => $this->lesson->primaryMaterial,
            'materials' => $this->lesson->materials()
                ->when($this->lesson->primary_material_id, fn ($query) => $query->whereKeyNot($this->lesson->primary_material_id))
                ->get(),
        ])->layout('layouts::app', ['title' => $this->lesson->title]);
    }
}
