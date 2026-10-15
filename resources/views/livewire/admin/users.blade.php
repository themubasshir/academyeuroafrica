<div class="space-y-6">
    <h1 class="text-2xl font-semibold tracking-tight">Users</h1>
    <input wire:model.live.debounce="search" placeholder="Search name/email…" class="w-full max-w-sm rounded-lg border-gray-300 text-sm">
    <div class="divide-y divide-gray-200 rounded-xl border border-gray-200 bg-white">
        @foreach ($users as $u)
            <div class="flex items-center justify-between px-5 py-3">
                <div>
                    <p class="font-medium text-sm">{{ $u->name }}</p>
                    <p class="text-xs text-gray-500">{{ $u->email }} · {{ $u->roles->pluck('name')->join(', ') ?: 'student' }} · {{ ucfirst($u->status) }}</p>
                </div>
                <button wire:click="toggleActive({{ $u->id }})" class="text-sm {{ $u->status === 'disabled' ? 'text-green-600' : 'text-red-600' }}">
                    {{ $u->status === 'disabled' ? 'Enable' : 'Disable' }}
                </button>
            </div>
        @endforeach
    </div>
</div>
