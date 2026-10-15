<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ route('trainer.dashboard') }}" class="text-sm text-eamn-700 hover:underline">&larr; Dashboard</a>
    <h1 class="text-2xl font-semibold tracking-tight">Groups — {{ $course->title }}</h1>
    <p class="text-sm text-gray-500">Optional. Students can complete the course without a group.</p>

    <form wire:submit="create" class="flex gap-2 rounded-xl border border-gray-200 bg-white p-4">
        <input wire:model="name" placeholder="New group name" class="flex-1 rounded-lg border-gray-300 text-sm">
        <button class="rounded-lg bg-eamn-700 px-4 py-2 text-sm font-semibold text-white">Create</button>
    </form>

    <div class="grid gap-4 sm:grid-cols-2">
        @forelse ($groups as $group)
            <div class="rounded-xl border border-gray-200 bg-white p-5">
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
            <p class="col-span-full rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500">No groups.</p>
        @endforelse
    </div>
</div>
