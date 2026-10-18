<div class="space-y-6">
    <x-ui.page-head register="Administration" title="Enrolments" />

    <form wire:submit="grant" class="card-pad space-y-4"><h2 class="h3">Grant enrolment</h2><div class="grid gap-4 sm:grid-cols-2"><select wire:model="userId" class="input" required><option value="">Student</option>@foreach ($users as $user)<option value="{{ $user->id }}">{{ $user->name }} — {{ $user->email }}</option>@endforeach</select><select wire:model="variantId" class="input" required><option value="">Course package</option>@foreach ($variants as $variant)<option value="{{ $variant->id }}">{{ $variant->course->title }} — {{ $variant->name }}</option>@endforeach</select><input wire:model="expiresAt" type="date" class="input" aria-label="Access expiration"><input wire:model="grantReason" class="input" placeholder="Reason for manual access" required></div><button class="btn-primary">Grant access</button></form>

    <div class="card flex flex-wrap gap-3 p-4"><input wire:model.live.debounce="search" class="input min-w-64 flex-1" placeholder="Search student" aria-label="Search enrolments"><select wire:model.live="status" class="input" aria-label="Filter status"><option value="">All statuses</option><option value="active">Active</option><option value="suspended">Suspended</option><option value="refunded">Refunded</option></select></div>

    <div class="ledger">
        @forelse ($enrolments as $enrolment)
            <div class="ledger-row"><div class="min-w-0"><p class="text-sm font-semibold">{{ $enrolment->user->name }}</p><p class="text-xs text-ink-500">{{ $enrolment->variant->course->title }} · {{ $enrolment->variant->name }} · expires {{ $enrolment->expires_at?->format('M j, Y') ?? 'never' }}</p></div><div class="flex shrink-0 items-center gap-3"><span class="folio text-xs">{{ ucfirst($enrolment->status) }}</span>@if ($enrolment->status === 'active')<button wire:click="suspend({{ $enrolment->id }})" wire:confirm="Suspend this enrolment?" class="text-sm font-medium text-red-700 hover:underline">Suspend</button>@elseif ($enrolment->status === 'suspended')<button wire:click="restore({{ $enrolment->id }})" class="text-sm font-medium text-emerald-700 hover:underline">Restore</button>@endif</div></div>
        @empty
            <x-ui.empty title="No enrolments" />
        @endforelse
    </div>
    {{ $enrolments->links() }}
</div>
