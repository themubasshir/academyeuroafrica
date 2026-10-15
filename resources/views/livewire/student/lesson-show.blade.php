<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ route('learn.course', $enrolment) }}" class="text-sm text-eamn-700 hover:underline">&larr; Back to course</a>
    <h1 class="text-2xl font-semibold tracking-tight">{{ $lesson->title }}</h1>

    @if ($video)
        <div class="aspect-video overflow-hidden rounded-xl bg-black">
            <iframe
                src="{{ app(\App\Services\Video\VideoManager::class)->embedUrl($video) }}?start={{ $positionSeconds }}"
                class="h-full w-full" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>
        </div>
    @endif

    @if ($lesson->content)
        <div class="prose max-w-none rounded-xl border border-gray-200 bg-white p-6">
            {!! nl2br(e($lesson->content)) !!}
        </div>
    @endif

    @if ($materials->isNotEmpty())
        <section>
            <h2 class="text-lg font-semibold">Materials</h2>
            <ul class="mt-2 space-y-1">
                @foreach ($materials as $material)
                    <li>
                        <a href="{{ route('files.material', $material) }}"
                           class="inline-flex items-center gap-2 text-sm text-eamn-700 hover:underline">
                            ⭳ {{ $material->original_name }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <button wire:click="markComplete"
            class="rounded-lg bg-eamn-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-eamn-800">
        Mark complete
    </button>
</div>
