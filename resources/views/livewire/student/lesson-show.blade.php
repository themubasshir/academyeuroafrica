<div class="mx-auto max-w-5xl space-y-6">
    <a href="{{ route('learn.course', $enrolment) }}" class="text-sm font-medium text-eamn-700 hover:underline">&larr; Back to course</a>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="h1">{{ $lesson->title }}</h1>
        <button wire:click="markComplete" wire:loading.attr="disabled" class="btn-primary">
            <span wire:loading.remove wire:target="markComplete">Mark complete</span>
            <span wire:loading wire:target="markComplete">Saving…</span>
        </button>
    </div>

    @if ($video)
        <div class="aspect-video overflow-hidden rounded-2xl bg-black shadow-lg">
            <iframe
                src="{{ app(\App\Services\Video\VideoManager::class)->embedUrl($video) }}?start={{ $positionSeconds }}"
                class="h-full w-full" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen
                title="Lesson video"></iframe>
        </div>
    @else
        <x-ui.empty title="No video for this lesson" message="Check the materials below." />
    @endif

    @if ($lesson->content)
        <div class="card-pad prose max-w-none text-gray-700">
            {!! nl2br(e($lesson->content)) !!}
        </div>
    @endif

    @if ($materials->isNotEmpty())
        <section aria-labelledby="materials-heading">
            <h2 id="materials-heading" class="h2">Materials</h2>
            <ul class="card mt-3 divide-y divide-gray-100">
                @foreach ($materials as $material)
                    <li>
                        <a href="{{ route('files.material', $material) }}"
                           class="flex items-center gap-2 px-4 py-3 text-sm font-medium text-eamn-700 hover:bg-eamn-50">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"/></svg>
                            <span class="truncate">{{ $material->original_name }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
