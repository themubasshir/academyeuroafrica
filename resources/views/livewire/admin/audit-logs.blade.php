<div class="space-y-6">
    <x-ui.page-head register="Administration" title="Audit logs" />
    <div class="ledger font-mono text-xs">
        @forelse ($logs as $log)
            <div class="px-5 py-3">
                <span class="text-ink-600">{{ $log->created_at->toIso8601String() }}</span>
                <span class="font-semibold text-ink-900">{{ $log->action }}</span>
                <span class="text-ink-600">by {{ $log->actor?->email ?? 'system' }}</span>
                @if ($log->subject_type)<span class="text-ink-600">· {{ class_basename($log->subject_type) }}#{{ $log->subject_id }}</span>@endif
            </div>
        @empty
            <x-ui.empty title="No audit entries" />
        @endforelse
    </div>
</div>
