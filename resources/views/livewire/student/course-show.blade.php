@php
    $tabs = [
        'overview'    => 'Overview',
        'lessons'     => 'Lessons',
        'materials'   => 'Materials',
        'live'        => 'Live Classes',
        'assignments' => 'Assignments',
        'quizzes'     => 'Quizzes',
    ];
    $completedCount = $progress->filter()->count();
    $lessonCount = $modules->sum(fn ($m) => $m->lessons->count());
    $requiredLessons = $modules->flatMap->lessons->where('is_required', true);
    $requiredCompleted = $requiredLessons->filter(fn ($lesson) => isset($progress[$lesson->id]))->count();
@endphp
<div class="mx-auto max-w-6xl space-y-6">
    <a href="{{ route('student.dashboard') }}" class="link text-sm">&larr; My courses</a>

    <x-ui.page-head :register="$course->programme->name" :title="$course->title" :folio="'Enrolment EAMN-'.$enrolment->id">
        <span class="badge-blue">{{ $enrolment->variant->name === 'Live cohort' ? 'Live Online Training' : $enrolment->variant->name }}</span>
    </x-ui.page-head>

    {{-- Workspace nav --}}
    <nav class="flex flex-wrap items-center gap-1 border-b border-paper-300" aria-label="Course workspace">
        @foreach ($tabs as $key => $label)
            @php $on = $tab === $key; @endphp
            <a href="{{ route('learn.course', $enrolment) }}?tab={{ $key }}" wire:navigate
               @class([
                   'relative -mb-px border-b-2 px-3 py-2.5 text-sm transition',
                   'border-seal-500 font-semibold text-ink-950' => $on,
                   'border-transparent font-medium text-ink-500 hover:border-paper-300 hover:text-ink-900' => ! $on,
               ])
               @if ($on) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    {{-- ================= OVERVIEW ================= --}}
    @if ($tab === 'overview')
        <div class="grid gap-8 lg:grid-cols-3">
            <div class="space-y-8 lg:col-span-2">
                @if ($course->description)
                    <section class="card-pad">
                        <p class="text-sm leading-relaxed text-ink-600">{{ $course->description }}</p>
                    </section>
                @endif

                <section aria-labelledby="ov-curriculum">
                    <div class="register-rule"><h2 id="ov-curriculum" class="register shrink-0">Curriculum at a glance</h2></div>
                    <div class="mt-4 space-y-4">
                        @forelse ($modules->take(3) as $module)
                            <div class="card overflow-hidden">
                                <div class="flex items-center justify-between border-b border-paper-200 bg-paper-50 px-5 py-3">
                                    <span class="text-sm font-semibold text-ink-800">{{ $module->title }}</span>
                                    <span class="folio text-ink-500">{{ $module->lessons->count() }} lessons</span>
                                </div>
                            </div>
                        @empty
                            <x-ui.empty title="No lessons yet" message="Your trainer hasn't published lessons." />
                        @endforelse
                        @if ($modules->count() > 3)
                            <a href="{{ route('learn.course', $enrolment) }}?tab=lessons" wire:navigate class="link text-sm">All {{ $modules->count() }} modules →</a>
                        @endif
                    </div>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="card-pad" aria-labelledby="ov-progress">
                    <h2 id="ov-progress" class="register">Your progress</h2>
                    <p class="mt-3 font-display text-3xl font-semibold text-ink-950">{{ $completedCount }}<span class="text-lg text-ink-500">/{{ $lessonCount }}</span></p>
                    <p class="mt-1 text-sm text-ink-600">lessons completed</p>
                    <p class="mt-1 text-xs text-ink-500">{{ $requiredCompleted }}/{{ $requiredLessons->count() }} required lessons complete</p>
                    @if ($lessonCount > 0)
                        <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-paper-200" role="progressbar" aria-valuenow="{{ round($completedCount / $lessonCount * 100) }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="h-full bg-reg-600" style="width: {{ round($completedCount / $lessonCount * 100) }}%"></div>
                        </div>
                    @endif
                </section>

                @if ($sessions->isNotEmpty())
                    <section aria-labelledby="ov-live">
                        <div class="register-rule"><h2 id="ov-live" class="register shrink-0">Next live class</h2></div>
                        @php $next = $sessions->first(); @endphp
                        <div class="card-pad mt-4">
                            <p class="text-sm font-semibold text-ink-900">{{ $next->title }}</p>
                            <p class="mt-0.5 text-xs text-ink-500">{{ $next->starts_at->setTimezone(auth()->user()->timezone ?? 'UTC')->format('M j, g:i A T') }}</p>
                            @if ($next->meeting_url)
                                <a href="{{ $next->meeting_url }}" target="_blank" rel="noopener" class="btn-secondary btn-sm mt-3 w-full">Join {{ ucfirst($next->provider) }}</a>
                            @endif
                        </div>
                    </section>
                @endif
            </aside>
        </div>
    @endif

    {{-- ================= LESSONS ================= --}}
    @if ($tab === 'lessons')
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
                                        <span class="badge-gray">{{ $lesson->is_required ? 'Required' : 'Optional' }}</span>
                                        @if ($lesson->video)<span class="badge-gray">video</span>@elseif($lesson->type === 'pdf')<span class="badge-gray">PDF</span>@endif
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
    @endif

    {{-- ================= MATERIALS ================= --}}
    @if ($tab === 'materials')
        <section aria-labelledby="materials-heading">
            <div class="register-rule"><h2 id="materials-heading" class="register shrink-0">Course materials</h2></div>
            <p class="mt-2 text-sm text-ink-600">Files shared by your trainer for this course.</p>

            @php
                $byLevel = $materials->groupBy(fn ($m) => class_basename($m->attachable_type));
            @endphp
            <div class="mt-4 space-y-6">
                @forelse (['Course' => 'Course resources', 'CourseModule' => 'Module files', 'Lesson' => 'Lesson files'] as $level => $heading)
                    @php $items = $byLevel->get($level, collect()); @endphp
                    @if ($items->isNotEmpty())
                        <div>
                            <h3 class="folio mb-2 text-ink-500">{{ $heading }}</h3>
                            <ul class="ledger">
                                @foreach ($items as $material)
                                    <li class="ledger-row">
                                        <a href="{{ route('files.material', $material) }}" class="flex min-w-0 flex-1 items-center gap-3">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-paper-300 bg-paper-50 folio !tracking-normal text-ink-600">{{ $material->typeLabel() }}</span>
                                            <span class="min-w-0">
                                                <span class="block truncate text-sm font-semibold text-ink-900">{{ $material->original_name }}</span>
                                                @if ($material->description)
                                                    <span class="block truncate text-xs text-ink-500">{{ $material->description }}</span>
                                                @elseif ($material->attachable_type !== \App\Models\Course::class && $material->attachable)
                                                    <span class="block truncate text-xs text-ink-500">{{ $material->attachable->title ?? '' }}</span>
                                                @endif
                                            </span>
                                        </a>
                                        <span class="flex shrink-0 items-center gap-3">
                                            @if ($material->group)<span class="badge-gold">{{ $material->group->name }}</span>@endif
                                            @if ($material->student_id === auth()->id())<span class="badge-amber">For you</span>@endif
                                            <span class="folio w-14 text-right text-ink-500">{{ $material->humanSize() }}</span>
                                            <svg class="h-4 w-4 text-reg-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"/></svg>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @empty
                    <x-ui.empty title="No materials" message="Your trainer hasn't shared any files for this course yet." />
                @endforelse
            </div>
        </section>
    @endif

    {{-- ================= LIVE CLASSES ================= --}}
    @if ($tab === 'live')
        <section aria-labelledby="live-heading">
            <div class="register-rule"><h2 id="live-heading" class="register shrink-0">Upcoming live classes</h2></div>
            <div class="ledger mt-4">
                @forelse ($sessions as $session)
                    <div class="px-5 py-4 sm:flex sm:items-center sm:justify-between sm:gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-ink-900">{{ $session->title }}</p>
                            <p class="mt-0.5 text-xs text-ink-500">
                                {{ $session->starts_at->setTimezone(auth()->user()->timezone ?? 'UTC')->format('l, M j · g:i A T') }}
                                · {{ $session->duration_minutes }} min
                                @if ($session->group) · <span class="text-seal-600">{{ $session->group->name }}</span>@endif
                            </p>
                        </div>
                        @if ($session->meeting_url)
                            <a href="{{ $session->meeting_url }}" target="_blank" rel="noopener" class="btn-primary btn-sm mt-3 shrink-0 sm:mt-0">Join {{ ucfirst($session->provider) }}</a>
                        @else
                            <span class="badge-gray mt-3 shrink-0 sm:mt-0">Link pending</span>
                        @endif
                    </div>
                @empty
                    <x-ui.empty title="None scheduled" message="Live classes appear here when your trainer schedules them." />
                @endforelse
            </div>
        </section>
    @endif

    {{-- ================= ASSIGNMENTS ================= --}}
    @if ($tab === 'assignments')
        <section aria-labelledby="assignments-heading">
            <div class="register-rule"><h2 id="assignments-heading" class="register shrink-0">Assignments</h2></div>
            <div class="ledger mt-4">
                @forelse ($assignments as $assignment)
                    <a href="{{ route('learn.assignment', [$enrolment, $assignment]) }}" class="ledger-row">
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-ink-900">{{ $assignment->title }}</span>
                            <span class="block text-xs text-ink-500">{{ $assignment->max_points }} points</span>
                        </span>
                        <span @class(['badge shrink-0', 'badge-amber' => $assignment->due_at && $assignment->due_at->isPast(), 'badge-gray' => !($assignment->due_at && $assignment->due_at->isPast())])>
                            {{ $assignment->due_at?->format('M j, Y') ?? 'No deadline' }}
                        </span>
                    </a>
                @empty
                    <x-ui.empty title="No assignments" message="Assignments appear here." />
                @endforelse
            </div>
        </section>
    @endif

    {{-- ================= QUIZZES ================= --}}
    @if ($tab === 'quizzes')
        <section aria-labelledby="quizzes-heading">
            <div class="register-rule"><h2 id="quizzes-heading" class="register shrink-0">Quizzes</h2></div>
            <div class="ledger mt-4">
                @forelse ($quizzes as $quiz)
                    <a href="{{ route('learn.quiz', [$enrolment, $quiz]) }}" class="ledger-row">
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-ink-900">{{ $quiz->title }}</span>
                            <span class="block text-xs text-ink-500">{{ $quiz->questions_count }} questions · pass at {{ $quiz->passing_score }}%</span>
                        </span>
                        <span class="shrink-0 text-sm font-semibold text-reg-700">Take →</span>
                    </a>
                @empty
                    <x-ui.empty title="No quizzes" />
                @endforelse
            </div>
        </section>
    @endif
</div>
