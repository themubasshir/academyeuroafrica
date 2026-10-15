<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <a href="{{ route('trainer.dashboard') }}" class="link text-sm">&larr; Dashboard</a>
        <div class="mt-3"><p class="register">{{ $course->programme->name }}</p><h1 class="h1 mt-1.5">{{ $course->title }}</h1></div>
    </div>
    <x-trainer.course-nav :course="$course" active="groups" />
    <p class="text-sm text-ink-500">Optional. Students can complete the course without a group.</p>

    <form wire:submit="create" class="card flex gap-3 p-4">
        <input wire:model="name" placeholder="New group name" class="input flex-1" aria-label="New group name">
        <button class="btn-primary">Create</button>
    </form>

    <div class="grid gap-4 sm:grid-cols-2">
        @forelse ($groups as $group)
            <div class="card-pad">
                <div class="flex items-center justify-between">
                    <h3 class="font-medium">{{ $group->name }}</h3>
                    <button wire:click="deleteGroup({{ $group->id }})" wire:confirm="Delete group?" class="text-xs text-red-700">Delete</button>
                </div>
                <ul class="mt-3 space-y-1">
                    @foreach ($students as $s)
                        <li class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:click="toggleMember({{ $group->id }}, {{ $s->id }})"
                                   @checked($group->members->contains($s->id)) class="rounded text-reg-600">
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
