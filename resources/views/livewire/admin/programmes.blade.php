<div class="space-y-6">
    <h1 class="h1">Programmes</h1>
    <form wire:submit="create" class="card flex gap-3 p-4">
        <input wire:model="name" placeholder="New programme name" class="input flex-1" aria-label="New programme name">
        <button class="btn-primary">Create</button>
    </form>
    <div class="divide-y divide-gray-200 card">
        @foreach ($programmes as $p)
            <div class="flex items-center justify-between px-5 py-4">
                <div>
                    <a href="{{ route('programme.show', $p) }}" class="font-medium text-eamn-700 hover:underline">{{ $p->name }}</a>
                    <p class="text-xs text-gray-500">{{ $p->courses_count }} courses · {{ ucfirst($p->status) }}</p>
                </div>
                <button wire:click="toggleStatus({{ $p->id }})" class="text-sm text-gray-600 hover:text-gray-900">
                    {{ $p->status === 'published' ? 'Unpublish' : 'Publish' }}
                </button>
            </div>
        @endforeach
    </div>
</div>
