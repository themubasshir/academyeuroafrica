<div class="space-y-6">
    <h1 class="h1">Courses</h1>
    <form wire:submit="create" class="space-y-3 card p-4">
        <div class="grid gap-3 sm:grid-cols-2">
            <input wire:model="title" placeholder="Course title" class="input" aria-label="Course title">
            <select wire:model="programmeId" class="input" aria-label="Programme">
                <option value="">Programme…</option>
                @foreach ($programmes as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
            </select>
        </div>
        <fieldset class="flex flex-wrap gap-4 text-sm">
            <legend class="sr-only">Delivery types</legend>
            @foreach (['live','prerecorded','hybrid','private'] as $t)
                <label class="flex items-center gap-1.5"><input type="checkbox" wire:model="deliveryTypes" value="{{ $t }}" class="rounded border-gray-300 text-eamn-600 focus:ring-eamn-500"> {{ ucfirst($t) }}</label>
            @endforeach
        </fieldset>
        <button class="btn-primary btn-sm w-fit">Create (draft)</button>
    </form>

    <div class="divide-y divide-gray-200 card">
        @foreach ($courses as $c)
            <div class="flex items-center justify-between px-5 py-4">
                <div>
                    <p class="font-medium">{{ $c->title }}</p>
                    <p class="text-xs text-gray-500">{{ $c->programme->name }} · {{ $c->variants->count() }} variants · {{ ucfirst($c->status) }}</p>
                </div>
                <div class="flex gap-3 text-sm">
                    <a href="{{ route('trainer.course.manage', $c) }}" class="text-eamn-700 hover:underline">Manage</a>
                    <button wire:click="toggleStatus({{ $c->id }})" class="text-gray-600">{{ $c->status === 'published' ? 'Unpublish' : 'Publish' }}</button>
                </div>
            </div>
        @endforeach
    </div>
</div>
