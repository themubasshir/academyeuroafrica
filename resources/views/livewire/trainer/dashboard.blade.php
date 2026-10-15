<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold tracking-tight">Trainer dashboard</h1>
        <a href="{{ route('trainer.availability') }}" class="text-sm font-medium text-eamn-700 hover:underline">Manage availability →</a>
    </div>

    @if ($pendingGrading)
        <div class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ $pendingGrading }} submission{{ $pendingGrading === 1 ? '' : 's' }} awaiting grading.
        </div>
    @endif

    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($courses as $course)
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <p class="text-xs text-eamn-700">{{ $course->programme->name }}</p>
                <h2 class="mt-1 font-semibold">{{ $course->title }}</h2>
                <p class="text-xs text-gray-400">{{ ucfirst($course->status) }}</p>
                <div class="mt-4 grid grid-cols-2 gap-1.5 text-sm">
                    <a href="{{ route('trainer.course.manage', $course) }}" class="rounded-lg bg-gray-50 px-2 py-1.5 text-center hover:bg-eamn-50">Content</a>
                    <a href="{{ route('trainer.sessions', $course) }}" class="rounded-lg bg-gray-50 px-2 py-1.5 text-center hover:bg-eamn-50">Sessions</a>
                    <a href="{{ route('trainer.groups', $course) }}" class="rounded-lg bg-gray-50 px-2 py-1.5 text-center hover:bg-eamn-50">Groups</a>
                    <a href="{{ route('trainer.assignments', $course) }}" class="rounded-lg bg-gray-50 px-2 py-1.5 text-center hover:bg-eamn-50">Assignments</a>
                    <a href="{{ route('trainer.quizzes', $course) }}" class="rounded-lg bg-gray-50 px-2 py-1.5 text-center hover:bg-eamn-50">Quizzes</a>
                    <a href="{{ route('trainer.students', $course) }}" class="rounded-lg bg-gray-50 px-2 py-1.5 text-center hover:bg-eamn-50">Students</a>
                </div>
            </div>
        @empty
            <p class="col-span-full rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
                No courses assigned yet.
            </p>
        @endforelse
    </div>
</div>
