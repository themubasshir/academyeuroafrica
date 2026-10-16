<div class="space-y-6">
    <x-ui.page-head register="Administration" title="Programmes" />

    <form wire:submit="save" class="card-pad space-y-4">
        <h2 class="h3">{{ $editingId ? 'Edit programme' : 'New programme' }}</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="text-sm font-medium sm:col-span-2">Name <input wire:model="name" class="input mt-1" required></label>
            <label class="text-sm font-medium">Status <select wire:model="status" class="input mt-1"><option value="draft">Draft</option><option value="published">Published</option><option value="archived">Archived</option></select></label>
            <label class="text-sm font-medium">Display order <input wire:model="sortOrder" type="number" min="0" class="input mt-1"></label>
            <label class="text-sm font-medium sm:col-span-2">Description <textarea wire:model="description" rows="4" class="input mt-1"></textarea></label>
        </div>
        @error('name')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
        <div class="flex gap-2"><button class="btn-primary">{{ $editingId ? 'Save programme' : 'Create programme' }}</button>@if ($editingId)<button type="button" wire:click="resetForm" class="btn-ghost">Cancel</button>@endif</div>
    </form>

    <div class="ledger">
        @forelse ($programmes as $programme)
            <div class="ledger-row">
                <div class="min-w-0"><p class="font-semibold">{{ $programme->name }}</p><p class="text-xs text-ink-500">{{ $programme->courses_count }} courses · {{ ucfirst($programme->status) }}</p></div>
                <div class="flex gap-3 text-sm font-medium"><button wire:click="edit({{ $programme->id }})" class="text-reg-700 hover:underline">Edit</button>@if ($programme->status !== 'archived')<button wire:click="archive({{ $programme->id }})" wire:confirm="Archive this programme?" class="text-red-700 hover:underline">Archive</button>@endif</div>
            </div>
        @empty
            <x-ui.empty title="No programmes" />
        @endforelse
    </div>
    {{ $programmes->links() }}
</div>
