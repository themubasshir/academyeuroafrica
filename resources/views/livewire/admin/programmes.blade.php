<div class="space-y-6">
    <h1 class="text-2xl font-semibold tracking-tight">Programmes</h1>
    <form wire:submit="create" class="flex gap-2 rounded-xl border border-gray-200 bg-white p-4">
        <input wire:model="name" placeholder="New programme name" class="flex-1 rounded-lg border-gray-300 text-sm">
        <button class="rounded-lg bg-eamn-700 px-4 py-2 text-sm font-semibold text-white">Create</button>
    </form>
    <div class="divide-y divide-gray-200 rounded-xl border border-gray-200 bg-white">
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
