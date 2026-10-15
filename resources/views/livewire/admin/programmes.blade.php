<div class="space-y-6">
    <x-ui.page-head register="Administration" title="Programmes" />

    <form wire:submit="create" class="card flex gap-3 p-4">
        <input wire:model="name" placeholder="New programme name" class="input flex-1" aria-label="New programme name">
        <button class="btn-primary">Create</button>
    </form>

    <div class="ledger">
        @foreach ($programmes as $p)
            <div class="ledger-row">
                <div class="min-w-0">
                    <a href="{{ route('programme.show', $p) }}" class="font-semibold text-ink-900 hover:text-reg-700">{{ $p->name }}</a>
                    <p class="text-xs text-ink-500">{{ $p->courses_count }} courses · {{ ucfirst($p->status) }}</p>
                </div>
                <button wire:click="toggleStatus({{ $p->id }})" class="shrink-0 text-sm font-medium text-ink-600 hover:text-ink-900">
                    {{ $p->status === 'published' ? 'Unpublish' : 'Publish' }}
                </button>
            </div>
        @endforeach
    </div>
</div>
