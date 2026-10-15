<div class="space-y-6">
    <h1 class="h1">Audit logs</h1>
    <div class="divide-y divide-gray-200 card font-mono text-xs">
        @forelse ($logs as $log)
            <div class="px-5 py-2.5">
                <span class="text-gray-400">{{ $log->created_at->toIso8601String() }}</span>
                <span class="font-semibold">{{ $log->action }}</span>
                <span class="text-gray-600">by {{ $log->actor?->email ?? 'system' }}</span>
                @if ($log->subject_type)<span class="text-gray-400">· {{ class_basename($log->subject_type) }}#{{ $log->subject_id }}</span>@endif
            </div>
        @empty
            <p class="p-8 text-center text-sm text-gray-500">No audit entries.</p>
        @endforelse
    </div>
</div>
