<div class="space-y-6">
    <x-ui.page-head register="Administration" title="Courses" />

    <form wire:submit="save" class="card-pad space-y-4">
        <h2 class="h3">{{ $editingId ? 'Edit course' : 'New course' }}</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="text-sm font-medium sm:col-span-2">Title <input wire:model="title" class="input mt-1" required></label>
            <label class="text-sm font-medium">Programme <select wire:model="programmeId" class="input mt-1" required><option value="">Choose programme</option>@foreach ($programmes as $programme)<option value="{{ $programme->id }}">{{ $programme->name }}</option>@endforeach</select></label>
            <label class="text-sm font-medium">Status <select wire:model="status" class="input mt-1"><option value="draft">Draft</option><option value="published">Published</option><option value="archived">Archived</option></select></label>
            <label class="text-sm font-medium">Delivery types <input wire:model="deliveryTypes" class="input mt-1" placeholder="live, prerecorded"></label>
            <label class="text-sm font-medium">Access days <input wire:model="accessDays" type="number" min="1" class="input mt-1"></label>
            <label class="text-sm font-medium">Primary trainer <select wire:model="trainerId" class="input mt-1"><option value="">No assignment</option>@foreach ($trainers as $trainer)<option value="{{ $trainer->id }}">{{ $trainer->name }}</option>@endforeach</select></label>
            <label class="text-sm font-medium">Display order <input wire:model="sortOrder" type="number" min="0" class="input mt-1"></label>
            <label class="text-sm font-medium sm:col-span-2">Description <textarea wire:model="description" rows="4" class="input mt-1"></textarea></label>
            <label class="text-sm font-medium sm:col-span-2">Learning objectives <span class="text-ink-500">(one per line)</span><textarea wire:model="learningOutcomes" rows="4" class="input mt-1"></textarea></label>
        </div>
        @error('title')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
        <div class="flex gap-2"><button class="btn-primary">{{ $editingId ? 'Save course' : 'Create course' }}</button>@if ($editingId)<button type="button" wire:click="resetForm" class="btn-ghost">Cancel</button>@endif</div>
    </form>

    <div class="ledger">
        @forelse ($courses as $course)
            <div class="ledger-row">
                <div class="min-w-0"><p class="truncate font-semibold">{{ $course->title }}</p><p class="text-xs text-ink-500">{{ $course->programme->name }} · {{ $course->variants->count() }} variants · {{ ucfirst($course->status) }}</p></div>
                <div class="flex shrink-0 gap-3 text-sm font-medium"><a href="{{ route('trainer.course.manage', $course) }}" class="text-reg-700 hover:underline">Curriculum</a><button wire:click="edit({{ $course->id }})" class="text-reg-700 hover:underline">Edit</button>@if ($course->status !== 'archived')<button wire:click="archive({{ $course->id }})" wire:confirm="Archive this course? Existing learning records are retained." class="text-red-700 hover:underline">Archive</button>@endif</div>
            </div>
        @empty
            <x-ui.empty title="No courses" />
        @endforelse
    </div>
    {{ $courses->links() }}
</div>
