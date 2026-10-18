<div class="space-y-6">
    <x-ui.page-head register="Administration" title="Programmes">
        <button type="button" wire:click="createProgramme" class="btn-primary">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14" /></svg>
            Add Programme
        </button>
    </x-ui.page-head>

    <p class="max-w-2xl text-sm text-ink-600">Group related courses into clear areas of study. Existing course relationships and permanent programme addresses remain unchanged when you edit.</p>

    <div class="ledger" aria-label="Existing programmes">
        @forelse ($programmes as $programme)
            <div class="ledger-row" wire:key="programme-{{ $programme->id }}">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-semibold text-ink-900">{{ $programme->name }}</p>
                        <span @class(['badge-green' => $programme->status === 'published', 'badge-gray' => $programme->status === 'draft', 'badge-amber' => $programme->status === 'archived'])>{{ ucfirst($programme->status) }}</span>
                    </div>
                    @if ($programme->description)<p class="mt-1 line-clamp-2 max-w-3xl text-sm text-ink-600">{{ $programme->description }}</p>@endif
                    <p class="folio mt-2">{{ $programme->courses_count }} {{ Str::plural('course', $programme->courses_count) }} · Display order {{ $programme->sort_order }}</p>
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <button type="button" wire:click="editProgramme({{ $programme->id }})" class="btn-secondary btn-sm">Edit</button>
                    @if ($programme->status !== 'archived')
                        <button type="button" wire:click="archive({{ $programme->id }})" wire:confirm="Archive this programme? Its courses and existing records will be retained." class="btn-ghost btn-sm !text-red-700">Archive</button>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-5"><x-ui.empty title="No programmes yet" message="Add the first programme to begin organising the course catalogue." /></div>
        @endforelse
    </div>
    {{ $programmes->links() }}

    <x-ui.modal open="showProgrammeModal" close="closeProgrammeModal" title-id="programme-dialog-title" description-id="programme-dialog-description" :confirm-close="true">
        <x-slot:header>
            <h2 id="programme-dialog-title" class="h2">{{ $editingId ? 'Edit programme' : 'Add programme' }}</h2>
            <p id="programme-dialog-description" class="mt-1 text-sm text-ink-600">{{ $editingId ? 'Update the catalogue details without changing its permanent identity.' : 'Create a new area of study for related courses.' }}</p>
        </x-slot:header>

        <form wire:submit="save" class="flex min-h-0 flex-1 flex-col">
            <div class="space-y-5 overflow-y-auto px-5 py-5 sm:px-6">
                <div>
                    <label for="programme-name" class="label">Name</label>
                    <input id="programme-name" wire:model="name" class="input" required autofocus aria-describedby="programme-name-error">
                    @error('name')<p id="programme-name-error" class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="programme-description" class="label">Description</label>
                    <textarea id="programme-description" wire:model="description" rows="5" class="input" aria-describedby="programme-description-error"></textarea>
                    @error('description')<p id="programme-description-error" class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="programme-status" class="label">Status</label>
                        <select id="programme-status" wire:model="status" class="input"><option value="draft">Draft</option><option value="published">Published</option><option value="archived">Archived</option></select>
                        @error('status')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="programme-order" class="label">Display order</label>
                        <input id="programme-order" wire:model="sortOrder" type="number" min="0" class="input">
                        @error('sortOrder')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
            <div class="flex shrink-0 flex-col-reverse gap-2 border-t border-paper-300 bg-paper-50 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                <button type="button" x-on:click="close()" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">{{ $editingId ? 'Save changes' : 'Create programme' }}</span>
                    <span wire:loading wire:target="save">Saving…</span>
                </button>
            </div>
        </form>
    </x-ui.modal>
</div>
