<div class="mx-auto max-w-2xl space-y-6">
    <a href="{{ route('trainer.dashboard') }}" class="link text-sm">&larr; Dashboard</a>
    <div>
        <p class="register">Private training</p>
        <h1 class="h1 mt-1.5">My availability</h1>
        <div class="mt-4 h-px w-full bg-paper-300"></div>
    </div>
    <p class="text-sm text-ink-500">Times shown in {{ auth()->user()->timezone ?? 'UTC' }}.</p>

    <form wire:submit="add" class="card-pad grid gap-4 sm:grid-cols-4">
        <div><label for="d" class="label">Date</label><input id="d" type="date" wire:model="date" class="input mt-1"></div>
        <div><label for="st" class="label">Start</label><input id="st" type="time" wire:model="startTime" class="input mt-1"></div>
        <div><label for="et" class="label">End</label><input id="et" type="time" wire:model="endTime" class="input mt-1"></div>
        <div class="flex items-end"><button class="btn-primary w-full">Add block</button></div>
    </form>

    <div class="space-y-2">
        @forelse ($slots as $slot)
            <div class="flex items-center justify-between card p-4">
                <p class="text-sm">
                    {{ $slot->starts_at->setTimezone($slot->timezone)->format('D, M j · g:i A') }}
                    — {{ $slot->ends_at->setTimezone($slot->timezone)->format('g:i A T') }}
                </p>
                <button wire:click="remove({{ $slot->id }})" wire:confirm="Remove this availability block?" class="text-sm font-medium text-red-700 hover:underline">Remove</button>
            </div>
        @empty
            <x-ui.empty title="No upcoming availability" />
        @endforelse
    </div>
</div>
