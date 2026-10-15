<div class="space-y-8">
    {{-- Welcome --}}
    <div class="card-pad bg-gradient-to-r from-eamn-700 to-eamn-900 text-white">
        <h1 class="h1">Welcome back, {{ auth()->user()->name }}</h1>
        <p class="mt-1 text-sm text-eamn-100">Pick up where you left off.</p>
    </div>

    {{-- Continue learning / My courses --}}
    <section aria-labelledby="courses-heading">
        <div class="flex items-center justify-between">
            <h2 id="courses-heading" class="h2">My courses</h2>
            <a href="{{ route('home') }}#programmes" class="text-sm font-medium text-eamn-700 hover:underline">Browse more →</a>
        </div>
        <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($enrolments as $enrolment)
                @php $course = $enrolment->variant->course; @endphp
                <a href="{{ route('learn.course', $enrolment) }}"
                   class="card group flex flex-col overflow-hidden transition hover:shadow-md">
                    <div class="h-28 bg-gradient-to-br from-eamn-600 to-eamn-900 p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-eamn-200">{{ $course->programme->name }}</p>
                        <h3 class="mt-1 font-semibold leading-snug text-white">{{ $course->title }}</h3>
                    </div>
                    <div class="flex flex-1 flex-col p-4">
                        <span class="badge-blue w-fit">{{ $enrolment->variant->name }}</span>
                        <span class="mt-auto pt-3 text-sm font-semibold text-eamn-700 group-hover:underline">Continue →</span>
                    </div>
                </a>
            @empty
                <div class="col-span-full">
                    <x-ui.empty title="No courses yet" message="Purchased courses appear here.">
                        <a href="{{ route('home') }}#programmes" class="btn-primary btn-sm mt-4">Browse programmes</a>
                    </x-ui.empty>
                </div>
            @endforelse
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <section aria-labelledby="live-heading">
            <h2 id="live-heading" class="h2">Upcoming live classes</h2>
            <div class="mt-4 space-y-3">
                @forelse ($upcoming as $s)
                    <div class="card-pad flex items-center gap-4">
                        <div class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-xl bg-eamn-50 text-eamn-700">
                            <span class="text-base font-bold leading-none">{{ $s->starts_at->format('d') }}</span>
                            <span class="text-[10px] uppercase">{{ $s->starts_at->format('M') }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900">{{ $s->title }}</p>
                            <p class="text-xs text-gray-500">{{ $s->starts_at->setTimezone(auth()->user()->timezone ?? 'UTC')->format('g:i A T') }}</p>
                        </div>
                    </div>
                @empty
                    <x-ui.empty title="None scheduled" message="Upcoming live classes appear here." />
                @endforelse
            </div>
        </section>

        <section aria-labelledby="due-heading">
            <h2 id="due-heading" class="h2">Assignments due</h2>
            <div class="mt-4 space-y-3">
                @forelse ($due as $a)
                    <a href="{{ route('learn.assignment', [$enrolments->firstWhere('variant.course_id', $a->course_id), $a]) }}"
                       class="card-pad flex items-center gap-4 transition hover:border-eamn-300">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M9 8h6M5 3h14a1 1 0 011 1v16a1 1 0 01-1 1H5a1 1 0 01-1-1V4a1 1 0 011-1z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900">{{ $a->title }}</p>
                            <p class="text-xs text-amber-700">Due {{ $a->due_at->format('M j, g:i A') }}</p>
                        </div>
                    </a>
                @empty
                    <x-ui.empty title="Nothing due" message="Pending assignments appear here." />
                @endforelse
            </div>
        </section>
    </div>
</div>
