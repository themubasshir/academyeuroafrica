<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ route('trainer.dashboard') }}" class="text-sm text-eamn-700 hover:underline">&larr; Dashboard</a>
    <h1 class="text-2xl font-semibold tracking-tight">Students — {{ $course->title }}</h1>

    <div class="divide-y divide-gray-200 rounded-xl border border-gray-200 bg-white">
        @forelse ($rows as $row)
            <div class="flex items-center justify-between px-5 py-4">
                <div>
                    <p class="font-medium text-sm">{{ $row['user']->name }}</p>
                    <p class="text-xs text-gray-500">Enrolled {{ $row['enrolled_at']->format('M j, Y') }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold">{{ $row['done'] }}/{{ $row['total'] }} lessons</p>
                    <div class="mt-1 h-1.5 w-32 overflow-hidden rounded-full bg-gray-200">
                        <div class="h-full bg-eamn-600" style="width: {{ min(100, $row['total'] ? round($row['done']/$row['total']*100) : 0) }}%"></div>
                    </div>
                </div>
            </div>
        @empty
            <p class="p-8 text-center text-sm text-gray-500">No students enrolled.</p>
        @endforelse
    </div>
</div>
