<div class="mx-auto max-w-5xl space-y-6">
    <a href="{{ route('admin.certificates') }}" class="link text-sm">&larr; Certificates</a>
    <x-ui.page-head register="Administration" title="Certificate Audit Log" folio="{{ $logs->total() }} entries" />

    <div class="ledger">
        @forelse ($logs as $log)
            <div class="px-5 py-3 text-sm">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <span class="font-semibold text-ink-900">{{ ucfirst($log->action) }}</span>
                    <span class="text-xs text-ink-500">{{ $log->created_at->format('j M Y H:i') }}</span>
                </div>
                <p class="mt-0.5 text-xs text-ink-600">
                    @if ($log->certificate)<span class="font-mono">{{ $log->certificate->number }}</span> · @endif
                    @if ($log->actor){{ $log->actor->name }} · @endif
                    {{ $log->detail }}
                </p>
            </div>
        @empty
            <x-ui.empty title="No audit entries" />
        @endforelse
    </div>
    {{ $logs->links() }}
</div>
