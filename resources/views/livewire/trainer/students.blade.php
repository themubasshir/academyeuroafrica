<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ route('trainer.dashboard') }}" class="text-sm text-eamn-700 hover:underline">&larr; Dashboard</a>
    <h1 class="h1">Students — {{ $course->title }}</h1>

    <div class="divide-y divide-gray-200 card">
        @forelse ($rows as $row)
            <div class="flex items-center justify-between px-5 py-4">
                <div>
                    <p class="font-medium text-sm">{{ $row['user']->name }}</p>
                    <p class="text-xs text-gray-500">Enrolled {{ $row['enrolled_at']->format('M j, Y') }}</p>
                </div>
                <div class="w-32 shrink-0 text-right">
                    <p class="text-xs font-medium text-gray-500">{{ $row['done'] }}/{{ $row['total'] }} lessons</p>
                    <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-gray-200" role="progressbar" aria-valuenow="{{ min(100, $row['total'] ? round($row['done']/$row['total']*100) : 0) }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="h-full rounded-full bg-eamn-600" style="width: {{ min(100, $row['total'] ? round($row['done']/$row['total']*100) : 0) }}%"></div>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-6"><x-ui.empty title="No students enrolled" /></div>
        @endforelse
    </div>
</div>
