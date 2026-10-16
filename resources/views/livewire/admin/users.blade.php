<div class="space-y-6">
    <x-ui.page-head register="Administration" title="Users">
        <div class="flex w-full max-w-xl gap-2"><input wire:model.live.debounce="search" placeholder="Search name/email…" class="input flex-1" aria-label="Search users"><select wire:model.live="role" class="input" aria-label="Filter by role"><option value="">All roles</option>@foreach ($roles as $role)<option value="{{ $role->name }}">{{ $role->label }}</option>@endforeach</select></div>
    </x-ui.page-head>

    @if ($editingId)
        <form wire:submit="save" class="card-pad space-y-4">
            <h2 class="h3">Edit user</h2>
            <div class="grid gap-4 sm:grid-cols-2"><label class="text-sm font-medium">Name <input wire:model="name" class="input mt-1"></label><label class="text-sm font-medium">Email <input wire:model="email" type="email" class="input mt-1"></label><label class="text-sm font-medium">Timezone <input wire:model="timezone" class="input mt-1" placeholder="Europe/Lisbon"></label></div>
            <fieldset><legend class="text-sm font-medium">Roles</legend><div class="mt-2 flex flex-wrap gap-4">@foreach ($roles as $role)<label class="flex items-center gap-2 text-sm"><input wire:model="selectedRoles" type="checkbox" value="{{ $role->name }}">{{ $role->label }}</label>@endforeach</div></fieldset>
            @if (in_array('trainer', $selectedRoles, true))<fieldset><legend class="text-sm font-medium">Trainer course assignments</legend><div class="mt-2 grid gap-2 sm:grid-cols-2">@foreach ($courses as $course)<label class="flex items-center gap-2 text-sm"><input wire:model="trainerCourses" type="checkbox" value="{{ $course->id }}">{{ $course->title }}</label>@endforeach</div></fieldset>@endif
            @error('email')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
            <div class="flex gap-2"><button class="btn-primary">Save user</button><button type="button" wire:click="resetForm" class="btn-ghost">Cancel</button></div>
        </form>
    @endif

    <div class="ledger">
        @forelse ($users as $user)
            <div class="ledger-row"><div class="flex min-w-0 items-center gap-3"><x-ui.avatar :name="$user->name" /><div class="min-w-0"><p class="truncate text-sm font-semibold">{{ $user->name }}</p><p class="truncate text-xs text-ink-500">{{ $user->email }} · {{ $user->roles->pluck('name')->join(', ') ?: 'student' }} · {{ ucfirst($user->status) }}</p></div></div><div class="flex shrink-0 gap-3 text-sm font-medium"><button wire:click="edit({{ $user->id }})" class="text-reg-700 hover:underline">Edit</button><button wire:click="toggleActive({{ $user->id }})" wire:confirm="{{ $user->status === 'disabled' ? 'Enable' : 'Disable' }} {{ $user->name }}?" class="{{ $user->status === 'disabled' ? 'text-emerald-700' : 'text-red-700' }} hover:underline">{{ $user->status === 'disabled' ? 'Enable' : 'Disable' }}</button></div></div>
        @empty
            <x-ui.empty title="No users found" />
        @endforelse
    </div>
    {{ $users->links() }}
</div>
