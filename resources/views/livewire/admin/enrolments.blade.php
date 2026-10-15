<div class="space-y-6">
    <x-ui.page-head register="Administration" title="Enrolments" />
    <div class="ledger">
        @forelse ($enrolments as $e)
            <div class="ledger-row">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-ink-900">{{ $e->user->name }}</p>
                    <p class="text-xs text-ink-500">{{ $e->variant->course->title }} · {{ $e->variant->name }}</p>
                </div>
                <p class="folio shrink-0">{{ $e->enrolled_at->format('M j, Y') }} · {{ ucfirst($e->status) }}</p>
            </div>
        @empty
            <x-ui.empty title="No enrolments" />
        @endforelse
    </div>
</div>
