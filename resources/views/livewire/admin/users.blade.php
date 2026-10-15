<div class="space-y-6">
    <x-ui.page-head register="Administration" title="Users">
        <input wire:model.live.debounce="search" placeholder="Search name/email…" class="input w-full max-w-xs" aria-label="Search users">
    </x-ui.page-head>

    <div class="ledger">
        @forelse ($users as $u)
            <div class="ledger-row">
                <div class="flex min-w-0 items-center gap-3">
                    <x-ui.avatar :name="$u->name" />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-ink-900">{{ $u->name }}</p>
                        <p class="truncate text-xs text-ink-500">{{ $u->email }} · {{ $u->roles->pluck('name')->join(', ') ?: 'student' }} · <span @class(['font-medium', 'text-emerald-700' => $u->status !== 'disabled', 'text-red-700' => $u->status === 'disabled'])>{{ ucfirst($u->status) }}</span></p>
                    </div>
                </div>
                <button wire:click="toggleActive({{ $u->id }})" wire:confirm="{{ $u->status === 'disabled' ? 'Enable' : 'Disable' }} {{ $u->name }}?"
                        class="{{ $u->status === 'disabled' ? 'text-emerald-700' : 'text-red-700' }} shrink-0 text-sm font-medium hover:underline">
                    {{ $u->status === 'disabled' ? 'Enable' : 'Disable' }}
                </button>
            </div>
        @empty
            <x-ui.empty title="No users found" />
        @endforelse
    </div>
</div>
