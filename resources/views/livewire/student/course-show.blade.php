<div class="mx-auto max-w-6xl space-y-8">
    {{-- Header --}}
    <div>
        <a href="{{ route('student.dashboard') }}" class="text-sm font-medium text-eamn-700 hover:underline">&larr; My courses</a>
        <div class="mt-3 card-pad bg-gradient-to-r from-eamn-700 to-eamn-900 text-white">
            <p class="text-xs font-semibold uppercase tracking-wider text-eamn-200">{{ $course->programme->name }}</p>
            <h1 class="mt-1 h1 sm:text-3xl">{{ $course->title }}</h1>
            <span class="badge mt-3 bg-white/15 text-white">{{ $enrolment->variant->name }}</span>
        </div>
    </div>

    <div class="grid gap-8 lg:grid-cols-3">
        <div class="space-y-8 lg:col-span-2">
            {{-- Classroom: announcements could slot here --}}
            <section aria-labelledby="curriculum-heading">
                <h2 id="curriculum-heading" class="h2">Curriculum</h2>
                <div class="mt-4 space-y-4">
                    @forelse ($modules as $module)
                        <div class="card overflow-hidden">
                            <div class="border-b border-gray-100 bg-gray-50 px-5 py-3 text-sm font-semibold text-gray-700">
                                {{ $module->title }}
                            </div>
                            <ul class="divide-y divide-gray-100">
                                @foreach ($module->lessons as $lesson)
                                    @php $done = isset($progress[$lesson->id]); @endphp
                                    <li>
                                        <a href="{{ route('learn.lesson', [$enrolment, $lesson]) }}"
                                           class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-eamn-50/50">
                                            <span @class(['flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                                                'bg-green-100 text-green-700' => $done,
                                                'bg-eamn-50 text-eamn-600' => ! $done])>
                                                @if ($done)✓@elseif($lesson->type === 'video')▶@else•@endif
                                            </span>
                                            <span class="flex-1 text-sm font-medium text-gray-800">{{ $lesson->title }}</span>
                                            @if ($lesson->video)<span class="badge-gray">video</span>@endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @empty
                        <x-ui.empty title="No lessons yet" message="Your trainer hasn't published lessons." />
                    @endforelse
                </div>
            </section>

            <section aria-labelledby="assignments-heading">
                <h2 id="assignments-heading" class="h2">Assignments</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($assignments as $assignment)
                        <a href="{{ route('learn.assignment', [$enrolment, $assignment]) }}"
                           class="card-pad flex items-center justify-between transition hover:border-eamn-300">
                            <span class="text-sm font-semibold text-gray-900">{{ $assignment->title }}</span>
                            <span @class(['badge', 'badge-amber' => $assignment->due_at && $assignment->due_at->isPast(), 'badge-gray' => !($assignment->due_at && $assignment->due_at->isPast())])>
                                {{ $assignment->due_at?->format('M j, Y') ?? 'No deadline' }}
                            </span>
                        </a>
                    @empty
                        <x-ui.empty title="No assignments" message="Assignments appear here." />
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section aria-labelledby="live-heading">
                <h2 id="live-heading" class="h2">Upcoming live</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($sessions as $session)
                        <div class="card-pad">
                            <p class="text-sm font-semibold text-gray-900">{{ $session->title }}</p>
                            <p class="mt-0.5 text-xs text-gray-500">{{ $session->starts_at->setTimezone(auth()->user()->timezone ?? 'UTC')->format('M j, g:i A T') }}</p>
                            @if ($session->meeting_url)
                                <a href="{{ $session->meeting_url }}" target="_blank" rel="noopener" class="btn-secondary btn-sm mt-3 w-full">Join {{ ucfirst($session->provider) }}</a>
                            @endif
                        </div>
                    @empty
                        <x-ui.empty title="None scheduled" />
                    @endforelse
                </div>
            </section>

            <section aria-labelledby="quizzes-heading">
                <h2 id="quizzes-heading" class="h2">Quizzes</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($quizzes as $quiz)
                        <a href="{{ route('learn.quiz', [$enrolment, $quiz]) }}"
                           class="card-pad block text-sm font-semibold text-gray-900 transition hover:border-eamn-300">
                            {{ $quiz->title }}
                        </a>
                    @empty
                        <x-ui.empty title="No quizzes" />
                    @endforelse
                </div>
            </section>

            @if ($materials->isNotEmpty())
                <section aria-labelledby="materials-heading">
                    <h2 id="materials-heading" class="h2">Materials</h2>
                    <ul class="card mt-4 divide-y divide-gray-100">
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
        </aside>
    </div>
</div>
