<div class="mx-auto max-w-2xl space-y-6">
    <a href="{{ route('trainer.dashboard') }}" class="text-sm text-eamn-700 hover:underline">&larr; Dashboard</a>
    <h1 class="text-2xl font-semibold tracking-tight">My availability</h1>
    <p class="text-sm text-gray-500">Times shown in {{ auth()->user()->timezone ?? 'UTC' }}.</p>

    @if (session('saved'))<div class="rounded-lg bg-green-50 px-4 py-2 text-sm text-green-700">{{ session('saved') }}</div>@endif

    <form wire:submit="add" class="grid gap-4 rounded-xl border border-gray-200 bg-white p-5 sm:grid-cols-4">
        <input type="date" wire:model="date" class="rounded-lg border-gray-300 text-sm">
        <input type="time" wire:model="startTime" class="rounded-lg border-gray-300 text-sm">
        <input type="time" wire:model="endTime" class="rounded-lg border-gray-300 text-sm">
        <button class="rounded-lg bg-eamn-700 px-4 py-2 text-sm font-semibold text-white">Add block</button>
    </form>

    <div class="space-y-2">
        @forelse ($slots as $slot)
            <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-white p-4">
                <p class="text-sm">
                    {{ $slot->starts_at->setTimezone($slot->timezone)->format('D, M j · g:i A') }}
                    — {{ $slot->ends_at->setTimezone($slot->timezone)->format('g:i A T') }}
                </p>
                <button wire:click="remove({{ $slot->id }})" class="text-sm text-red-600">Remove</button>
            </div>
        @empty
            <p class="rounded-xl border border-dashed border-gray-300 bg-white p-6 text-center text-sm text-gray-500">No upcoming availability.</p>
        @endforelse
    </div>
</div>
