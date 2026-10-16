<div class="space-y-6">
    <x-ui.page-head register="Administration" title="Materials" />

    <div class="card flex flex-wrap gap-3 p-4">
        <input wire:model.live.debounce="search" class="input min-w-64 flex-1" placeholder="Search filename or description" aria-label="Search materials">
        <select wire:model.live="visibility" class="input" aria-label="Filter material visibility">
            <option value="">All visibility</option>
            <option value="enrolled">Enrolled students</option>
            <option value="trainers">Trainers only</option>
        </select>
    </div>

    <div class="card overflow-x-auto">
        <table class="table-base">
            <thead class="bg-paper-50"><tr><th>Material</th><th>Course area</th><th>Audience</th><th>Uploader</th><th class="text-right">Action</th></tr></thead>
            <tbody>
                @forelse ($materials as $material)
                    <tr>
                        <td><p class="font-medium">{{ $material->description ?: $material->original_name }}</p><p class="text-xs text-ink-500">{{ $material->typeLabel() }} · {{ $material->humanSize() }}</p></td>
                        <td>{{ $material->course()?->title ?? 'Unassigned' }}</td>
                        <td>{{ ucfirst($material->visibility) }}{{ $material->student ? ' · '.$material->student->name : '' }}{{ $material->group ? ' · '.$material->group->name : '' }}</td>
                        <td>{{ $material->uploader?->name ?? 'Deleted user' }}</td>
                        <td class="text-right"><button wire:click="delete({{ $material->id }})" wire:confirm="Delete this private material?" class="text-sm font-medium text-red-700 hover:underline">Delete</button></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-ink-500">No materials found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $materials->links() }}
</div>
