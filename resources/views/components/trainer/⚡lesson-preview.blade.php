<?php

use App\Models\Course;
use App\Models\Lesson;
use Livewire\Component;

new class extends Component
{
    public Course $course;

    public Lesson $lesson;

    public function mount(Course $course, Lesson $lesson): void
    {
        $this->authorize('manageContent', $course);
        abort_unless($lesson->module->course_id === $course->id, 404);
        $this->lesson = $lesson->load(['video', 'primaryMaterial', 'materials']);
    }
};
?>

<div class="mx-auto max-w-5xl space-y-6">
    <a href="{{ route('trainer.course.manage', $course) }}" class="link text-sm">&larr; Back to course builder</a>
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="register">Lesson preview</p>
            <h1 class="h1 mt-1.5">{{ $lesson->title }}</h1>
        </div>
        <span class="{{ $lesson->status === 'published' ? 'badge-green' : 'badge-gray' }}">{{ ucfirst($lesson->status) }}</span>
    </div>

    @if ($lesson->type === 'pdf' && $lesson->primaryMaterial)
        <iframe src="{{ route('files.material.inline', $lesson->primaryMaterial) }}" class="h-[70vh] w-full rounded-lg border border-paper-300 bg-white" title="{{ $lesson->title }} PDF preview"></iframe>
    @elseif ($lesson->type === 'video' && $lesson->video?->provider === 'upload' && $lesson->primaryMaterial)
        <video controls preload="metadata" class="aspect-video w-full rounded-lg bg-ink-950">
            <source src="{{ route('files.material.stream', $lesson->primaryMaterial) }}" type="{{ $lesson->primaryMaterial->mime }}">
        </video>
    @elseif ($lesson->type === 'video' && $lesson->video)
        <div class="aspect-video overflow-hidden rounded-lg bg-ink-950">
            <iframe src="{{ app(\App\Services\Video\VideoManager::class)->embedUrl($lesson->video) }}" class="h-full w-full" allow="fullscreen; picture-in-picture" allowfullscreen title="Lesson video preview"></iframe>
        </div>
    @endif

    @if ($lesson->content)
        <article class="card-pad prose max-w-none text-ink-700">{!! $lesson->content !!}</article>
    @endif

    <p class="text-sm text-ink-500">
        {{ $lesson->is_required ? 'Required' : 'Optional' }} lesson
        @if ($lesson->type === 'video')
            · completes at {{ $lesson->completion_threshold }}% playback progress
        @elseif ($lesson->type === 'pdf')
            · {{ $lesson->requires_acknowledgement ? 'learner acknowledgement required' : 'completes when opened' }}
        @endif
        .
    </p>
</div>
