<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ route('trainer.dashboard') }}" class="text-sm text-eamn-700 hover:underline">&larr; Dashboard</a>
    <h1 class="h1">Groups — {{ $course->title }}</h1>
    <p class="text-sm text-gray-500">Optional. Students can complete the course without a group.</p>

    <form wire:submit="create" class="card flex gap-3 p-4">
        <input wire:model="name" placeholder="New group name" class="input flex-1" aria-label="New group name">
        <button class="btn-primary">Create</button>
    </form>

    <div class="grid gap-4 sm:grid-cols-2">
        @forelse ($groups as $group)
            <div class="card-pad">
                <div class="flex items-center justify-between">
                    <h3 class="font-medium">{{ $group->name }}</h3>
                    <button wire:click="deleteGroup({{ $group->id }})" wire:confirm="Delete group?" class="text-xs text-red-600">Delete</button>
                </div>
                <ul class="mt-3 space-y-1">
                    @foreach ($students as $s)
                        <li class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:click="toggleMember({{ $group->id }}, {{ $s->id }})"
                                   @checked($group->members->contains($s->id)) class="rounded text-eamn-600">
                            {{ $s->name }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @empty
            <div class="col-span-full"><x-ui.empty title="No groups" message="Optional — students can complete the course without one." /></div>
        @endforelse
    </div>
</div>
