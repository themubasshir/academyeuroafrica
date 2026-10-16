<div class="mx-auto max-w-4xl space-y-8">
    <div class="flex flex-wrap items-end justify-between gap-4"><x-ui.page-head register="Student register" title="Notifications" :folio="'Ref EAMN-'.auth()->id()" /><button wire:click="markAllAsRead" type="button" class="btn-ghost btn-sm">Mark all as read</button></div>
    <div class="ledger">
        @forelse ($notifications as $notification)
            <div class="ledger-row {{ $notification->read_at ? '' : 'bg-reg-50/50' }}"><div class="min-w-0 flex-1"><p class="text-sm font-semibold text-ink-900">{{ $notification->data['title'] ?? 'Academy update' }}</p><p class="mt-1 text-sm text-ink-600">{{ $notification->data['message'] ?? 'You have a new Academy notification.' }}</p><p class="mt-1 text-xs text-ink-500">{{ $notification->created_at->diffForHumans() }}</p></div>@unless ($notification->read_at)<button wire:click="markAsRead('{{ $notification->id }}')" class="btn-ghost btn-sm" type="button">Mark read</button>@endunless</div>
        @empty
            <x-ui.empty title="No notifications yet" />
        @endforelse
    </div>
    {{ $notifications->links() }}
</div>
