<div class="mx-auto max-w-6xl space-y-8">
    <a href="{{ route('student.dashboard') }}" class="link text-sm">&larr; My courses</a>

    <x-ui.page-head :register="$course->programme->name" :title="$course->title" :folio="'Enrolment EAMN-'.$enrolment->id">
        <span class="badge-blue">{{ $enrolment->variant->name }}</span>
    </x-ui.page-head>

    <div class="grid gap-8 lg:grid-cols-3">
        <div class="space-y-8 lg:col-span-2">
            <section aria-labelledby="curriculum-heading">
                <div class="register-rule"><h2 id="curriculum-heading" class="register shrink-0">Curriculum</h2></div>
                <div class="mt-4 space-y-4">
                    @forelse ($modules as $module)
                        <div class="card overflow-hidden">
                            <div class="border-b border-paper-200 bg-paper-50 px-5 py-3 text-sm font-semibold text-ink-800">
                                {{ $module->title }}
                            </div>
                            <ul class="divide-y divide-paper-200">
                                @foreach ($module->lessons as $lesson)
                                    @php $done = isset($progress[$lesson->id]); @endphp
                                    <li>
                                        <a href="{{ route('learn.lesson', [$enrolment, $lesson]) }}"
                                           class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-reg-50/60">
                                            <span @class(['flex h-6 w-6 shrink-0 items-center justify-center rounded-full border text-[11px] font-bold',
                                                'border-green-300 bg-green-50 text-green-700' => $done,
                                                'border-paper-300 bg-paper-50 text-ink-500' => ! $done])>
                                                @if ($done)✓@elseif($lesson->type === 'video')▶@else·@endif
                                            </span>
                                            <span class="flex-1 text-sm font-medium text-ink-800">{{ $lesson->title }}</span>
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
                <div class="register-rule"><h2 id="assignments-heading" class="register shrink-0">Assignments</h2></div>
                <div class="ledger mt-4">
                    @forelse ($assignments as $assignment)
                        <a href="{{ route('learn.assignment', [$enrolment, $assignment]) }}" class="ledger-row">
                            <span class="min-w-0 text-sm font-semibold text-ink-900">{{ $assignment->title }}</span>
                            <span @class(['badge shrink-0', 'badge-amber' => $assignment->due_at && $assignment->due_at->isPast(), 'badge-gray' => !($assignment->due_at && $assignment->due_at->isPast())])>
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
                <div class="register-rule"><h2 id="live-heading" class="register shrink-0">Upcoming live</h2></div>
                <div class="ledger mt-4">
                    @forelse ($sessions as $session)
                        <div class="px-5 py-4">
                            <p class="text-sm font-semibold text-ink-900">{{ $session->title }}</p>
                            <p class="mt-0.5 text-xs text-ink-500">{{ $session->starts_at->setTimezone(auth()->user()->timezone ?? 'UTC')->format('M j, g:i A T') }}</p>
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
                <div class="register-rule"><h2 id="quizzes-heading" class="register shrink-0">Quizzes</h2></div>
                <div class="ledger mt-4">
                    @forelse ($quizzes as $quiz)
                        <a href="{{ route('learn.quiz', [$enrolment, $quiz]) }}" class="ledger-row">
                            <span class="text-sm font-semibold text-ink-900">{{ $quiz->title }}</span>
                            <span class="shrink-0 text-sm font-semibold text-reg-700">Take →</span>
                        </a>
                    @empty
                        <x-ui.empty title="No quizzes" />
                    @endforelse
                </div>
            </section>

            @if ($materials->isNotEmpty())
                <section aria-labelledby="materials-heading">
                    <div class="register-rule"><h2 id="materials-heading" class="register shrink-0">Materials</h2></div>
                    <ul class="ledger mt-4">
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
        </aside>
    </div>
</div>
