<div class="space-y-6">
    <x-ui.page-head register="Administration" title="Course Packages" />

    <p class="text-sm text-ink-600">
        Imported commercial packages. Nothing is purchasable until a variant is
        <strong>approved</strong> <em>and</em> <strong>published</strong>, and its course is published.
        Variants with no fixed price cannot be approved.
    </p>

    <div class="ledger">
        @foreach ($variants as $v)
            <div class="ledger-row">
                <div class="min-w-0 flex-1">
                    <p class="truncate font-semibold text-ink-900">
                        {{ $v->course->title }} — {{ $v->name }}
                    </p>
                    <p class="text-xs text-ink-500">
                        {{ $v->course->programme->name }} · {{ ucfirst($v->delivery_type) }}
                        · {{ $v->duration ?? 'no duration' }}
                    </p>
                    <p class="mt-0.5 text-xs text-ink-400">
                        {{ $v->price_source }}
                    </p>
                    @if ($v->inclusions)
                        <p class="mt-0.5 text-xs text-ink-500">
                            {{ implode(' · ', $v->inclusions) }}
                        </p>
                    @endif
                </div>

                <div class="flex shrink-0 flex-col items-end gap-1 text-sm">
                    <span class="font-semibold text-ink-900">{{ $v->priceFormatted() }}</span>
                    <span class="text-xs {{ $v->approval_status === 'approved' ? 'text-green-700' : 'text-amber-600' }}">
                        {{ ucfirst($v->approval_status) }}
                    </span>
                    <span class="text-xs {{ $v->status === 'published' ? 'text-green-700' : 'text-ink-500' }}">
                        {{ ucfirst($v->status) }}
                    </span>
                </div>

                <div class="flex shrink-0 flex-col gap-1 text-sm">
                    <button wire:click="edit({{ $v->id }})" class="font-medium text-reg-700 hover:underline">Edit</button>
                    @if ($v->approval_status !== 'approved')
                        <button wire:click="approve({{ $v->id }})" class="font-medium text-green-700 hover:underline">Approve</button>
                    @else
                        <button wire:click="unapprove({{ $v->id }})" class="font-medium text-amber-700 hover:underline">Unapprove</button>
                        <button wire:click="togglePublish({{ $v->id }})" class="font-medium text-ink-600 hover:text-ink-900">
                            {{ $v->status === 'published' ? 'Unpublish' : 'Publish' }}
                        </button>
                    @endif
                </div>
            </div>

            @if (isset($draft[$v->id]))
                <div class="card space-y-3 p-4">
                    <div class="grid gap-3 sm:grid-cols-3">
                        <input wire:model="draft.{{ $v->id }}.name" class="input" aria-label="Package name">
                        <input wire:model="draft.{{ $v->id }}.price" type="number" step="0.01" min="0"
                               placeholder="Price EUR (blank = POA)" class="input" aria-label="Price">
                        <input wire:model="draft.{{ $v->id }}.duration" placeholder="Duration" class="input" aria-label="Duration">
                    </div>
                    <div class="flex gap-3">
                        <button wire:click="save({{ $v->id }})" class="btn-primary btn-sm">Save</button>
                        <button wire:click="$unset('draft.{{ $v->id }}')" class="btn-sm text-ink-500">Cancel</button>
                    </div>
                    @error("draft.{$v->id}.price") <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    @error("draft.{$v->id}.name") <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            @endif
        @endforeach
    </div>
</div>
