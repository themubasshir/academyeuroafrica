<div class="mx-auto max-w-5xl space-y-6">
    <a href="{{ route('learn.course', $enrolment) }}" class="link text-sm">&larr; Back to course</a>

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <p class="register">Lesson</p>
            <h1 class="h1 mt-1.5">{{ $lesson->title }}</h1>
        </div>
        <button wire:click="markComplete" wire:loading.attr="disabled" class="btn-primary">
            <span wire:loading.remove wire:target="markComplete">Mark complete</span>
            <span wire:loading wire:target="markComplete">Saving…</span>
        </button>
    </div>
    <div class="h-px w-full bg-paper-300"></div>

    @if ($video)
        <div class="aspect-video overflow-hidden rounded-lg bg-ink-950 shadow-md">
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
            <div class="register-rule"><h2 id="materials-heading" class="register shrink-0">Materials</h2></div>
            <ul class="ledger mt-3">
                @foreach ($materials as $material)
                    <li>
                        <a href="{{ route('files.material', $material) }}"
                           class="flex items-center gap-2 px-5 py-3 text-sm font-medium text-reg-700 transition hover:bg-reg-50/60">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"/></svg>
                            <span class="truncate">{{ $material->original_name }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
