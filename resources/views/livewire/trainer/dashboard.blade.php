<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="h1">Trainer dashboard</h1>
        <a href="{{ route('trainer.availability') }}" class="btn-secondary btn-sm">Manage availability</a>
    </div>

    @if ($pendingGrading)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800" role="status">
            {{ $pendingGrading }} submission{{ $pendingGrading === 1 ? '' : 's' }} awaiting grading.
        </div>
    @endif

    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($courses as $course)
            <div class="card flex flex-col overflow-hidden">
                <div class="bg-gradient-to-br from-eamn-600 to-eamn-900 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-eamn-200">{{ $course->programme->name }}</p>
                    <h2 class="mt-1 font-semibold text-white">{{ $course->title }}</h2>
                </div>
                <div class="p-4">
                    <span @class(['badge', 'badge-green' => $course->status === 'published', 'badge-gray' => $course->status !== 'published'])>{{ ucfirst($course->status) }}</span>
                    <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                        <a href="{{ route('trainer.course.manage', $course) }}" class="rounded-lg bg-gray-50 px-3 py-2 text-center font-medium text-gray-700 transition hover:bg-eamn-50 hover:text-eamn-800">Content</a>
                        <a href="{{ route('trainer.sessions', $course) }}" class="rounded-lg bg-gray-50 px-3 py-2 text-center font-medium text-gray-700 transition hover:bg-eamn-50 hover:text-eamn-800">Sessions</a>
                        <a href="{{ route('trainer.groups', $course) }}" class="rounded-lg bg-gray-50 px-3 py-2 text-center font-medium text-gray-700 transition hover:bg-eamn-50 hover:text-eamn-800">Groups</a>
                        <a href="{{ route('trainer.assignments', $course) }}" class="rounded-lg bg-gray-50 px-3 py-2 text-center font-medium text-gray-700 transition hover:bg-eamn-50 hover:text-eamn-800">Assignments</a>
                        <a href="{{ route('trainer.quizzes', $course) }}" class="rounded-lg bg-gray-50 px-3 py-2 text-center font-medium text-gray-700 transition hover:bg-eamn-50 hover:text-eamn-800">Quizzes</a>
                        <a href="{{ route('trainer.students', $course) }}" class="rounded-lg bg-gray-50 px-3 py-2 text-center font-medium text-gray-700 transition hover:bg-eamn-50 hover:text-eamn-800">Students</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full"><x-ui.empty title="No courses assigned yet" /></div>
        @endforelse
    </div>
</div>
