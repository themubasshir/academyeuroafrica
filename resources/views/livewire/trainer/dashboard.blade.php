<div class="space-y-6">
    <x-ui.page-head register="Trainer register" title="Trainer dashboard" :folio="'Ref EAMN-'.auth()->id()">
        <a href="{{ route('trainer.availability') }}" class="btn-secondary btn-sm">Manage availability</a>
    </x-ui.page-head>

    @if ($pendingGrading)
        <div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800" role="status">
            {{ $pendingGrading }} submission{{ $pendingGrading === 1 ? '' : 's' }} awaiting grading.
        </div>
    @endif

    <section aria-labelledby="courses-heading">
        <div class="register-rule"><h2 id="courses-heading" class="register shrink-0">Assigned courses</h2></div>
        <div class="mt-4 space-y-4">
            @forelse ($courses as $course)
                <div class="card overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-paper-200 bg-paper-50 px-5 py-3">
                        <div class="min-w-0">
                            <p class="folio">{{ $course->programme->name }}</p>
                            <h3 class="mt-0.5 font-display text-base font-semibold text-ink-950">{{ $course->title }}</h3>
                        </div>
                        <span @class(['badge', 'badge-green' => $course->status === 'published', 'badge-gray' => $course->status !== 'published'])>{{ ucfirst($course->status) }}</span>
                    </div>
                    <nav class="flex flex-wrap items-center gap-x-1 gap-y-1 px-3 py-2" aria-label="Manage {{ $course->title }}">
                        @foreach ([
                            'Content' => 'trainer.course.manage',
                            'Sessions' => 'trainer.sessions',
                            'Groups' => 'trainer.groups',
                            'Assignments' => 'trainer.assignments',
                            'Quizzes' => 'trainer.quizzes',
                            'Students' => 'trainer.students',
                        ] as $label => $route)
                            <a href="{{ route($route, $course) }}" class="rounded-md px-3 py-2 text-sm font-medium text-ink-600 transition hover:bg-reg-50 hover:text-reg-700">{{ $label }}</a>
                        @endforeach
                    </nav>
                </div>
            @empty
                <x-ui.empty title="No courses assigned yet" message="Courses you teach appear here once assigned." />
            @endforelse
        </div>
    </section>
</div>
