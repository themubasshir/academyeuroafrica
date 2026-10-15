<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <a href="{{ route('trainer.dashboard') }}" class="link text-sm">&larr; Dashboard</a>
        <div class="mt-3"><p class="register">{{ $course->programme->name }}</p><h1 class="h1 mt-1.5">{{ $course->title }}</h1></div>
    </div>
    <x-trainer.course-nav :course="$course" active="students" />

    <div class="divide-y divide-paper-200 card">
        @forelse ($rows as $row)
            <div class="flex items-center justify-between px-5 py-4">
                <div>
                    <p class="font-medium text-sm">{{ $row['user']->name }}</p>
                    <p class="text-xs text-ink-500">Enrolled {{ $row['enrolled_at']->format('M j, Y') }}</p>
                </div>
                <div class="w-32 shrink-0 text-right">
                    <p class="text-xs font-medium text-ink-500">{{ $row['done'] }}/{{ $row['total'] }} lessons</p>
                    <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-paper-200" role="progressbar" aria-valuenow="{{ min(100, $row['total'] ? round($row['done']/$row['total']*100) : 0) }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="h-full rounded-full bg-reg-600" style="width: {{ min(100, $row['total'] ? round($row['done']/$row['total']*100) : 0) }}%"></div>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-6"><x-ui.empty title="No students enrolled" /></div>
        @endforelse
    </div>
</div>
