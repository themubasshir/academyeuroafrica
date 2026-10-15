<div class="mx-auto max-w-5xl space-y-6">
    <x-ui.page-head register="Programme" :title="$programme->name" />
    @if ($programme->description)
        <p class="max-w-2xl leading-relaxed text-ink-600">{{ $programme->description }}</p>
    @endif

    <div class="ledger">
        @forelse ($courses as $course)
            <a href="{{ route('course.show', $course) }}" class="ledger-row group">
                <span class="min-w-0 flex-1">
                    <span class="block font-display text-base font-semibold text-ink-950 group-hover:text-reg-700">{{ $course->title }}</span>
                    <span class="mt-0.5 block line-clamp-2 text-sm text-ink-500">{{ $course->description }}</span>
                    <span class="mt-2 block space-y-0.5">
                        @foreach ($course->variants as $variant)
                            <span class="flex justify-between gap-4 text-xs text-ink-600">
                                <span>{{ $variant->name }}</span>
                                <span class="font-medium">{{ $variant->priceFormatted() }}</span>
                            </span>
                        @endforeach
                    </span>
                </span>
                <span class="shrink-0 self-start text-sm font-semibold text-reg-700">View →</span>
            </a>
        @empty
            <x-ui.empty title="No courses available yet" />
        @endforelse
    </div>
</div>
