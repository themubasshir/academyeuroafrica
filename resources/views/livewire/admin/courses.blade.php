<div class="space-y-6">
    <x-ui.page-head register="Administration" title="Courses" />

    <form wire:submit="create" class="card space-y-3 p-4">
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
                <label class="flex items-center gap-1.5 text-ink-700"><input type="checkbox" wire:model="deliveryTypes" value="{{ $t }}" class="rounded border-paper-300 text-reg-600 focus:ring-reg-600/40"> {{ ucfirst($t) }}</label>
            @endforeach
        </fieldset>
        <button class="btn-primary btn-sm w-fit">Create (draft)</button>
    </form>

    <div class="ledger">
        @foreach ($courses as $c)
            <div class="ledger-row">
                <div class="min-w-0">
                    <p class="truncate font-semibold text-ink-900">{{ $c->title }}</p>
                    <p class="text-xs text-ink-500">{{ $c->programme->name }} · {{ $c->variants->count() }} variants · {{ ucfirst($c->status) }}</p>
                </div>
                <div class="flex shrink-0 gap-4 text-sm">
                    <a href="{{ route('trainer.course.manage', $c) }}" class="font-medium text-reg-700 hover:underline">Manage</a>
                    <button wire:click="toggleStatus({{ $c->id }})" class="font-medium text-ink-600 hover:text-ink-900">{{ $c->status === 'published' ? 'Unpublish' : 'Publish' }}</button>
                </div>
            </div>
        @endforeach
    </div>
</div>
