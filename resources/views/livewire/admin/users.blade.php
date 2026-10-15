<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="h1">Users</h1>
        <input wire:model.live.debounce="search" placeholder="Search name/email…" class="input max-w-sm" aria-label="Search users">
    </div>

    <div class="card divide-y divide-gray-100">
        @forelse ($users as $u)
            <div class="flex items-center justify-between gap-4 px-5 py-4">
                <div class="flex min-w-0 items-center gap-3">
                    <x-ui.avatar :name="$u->name" />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-gray-900">{{ $u->name }}</p>
                        <p class="truncate text-xs text-gray-500">{{ $u->email }} · {{ $u->roles->pluck('name')->join(', ') ?: 'student' }} · <span @class(['font-medium', 'text-green-700' => $u->status !== 'disabled', 'text-red-600' => $u->status === 'disabled'])>{{ ucfirst($u->status) }}</span></p>
                    </div>
                </div>
                <button wire:click="toggleActive({{ $u->id }})" wire:confirm="{{ $u->status === 'disabled' ? 'Enable' : 'Disable' }} {{ $u->name }}?"
                        class="{{ $u->status === 'disabled' ? 'text-green-700' : 'text-red-600' }} text-sm font-medium hover:underline">
                    {{ $u->status === 'disabled' ? 'Enable' : 'Disable' }}
                </button>
            </div>
        @empty
            <div class="p-6"><x-ui.empty title="No users found" /></div>
        @endforelse
    </div>
</div>
