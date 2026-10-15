<div class="mx-auto max-w-3xl space-y-8">
    <x-ui.page-head register="Student register" title="Private training" :folio="'Ref EAMN-'.auth()->id()">
        <span class="badge-gold">{{ $credits }} credit{{ $credits === 1 ? '' : 's' }}</span>
    </x-ui.page-head>

    <section class="card-pad">
        <h2 class="h3">Book a session</h2>
        @if ($credits < 1)
            <p class="mt-2 text-sm text-ink-500">No session credits. Purchase a package to book.</p>
        @else
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="trainer" class="label">Trainer</label>
                    <select id="trainer" wire:model.live="trainerId" class="input mt-1">
                        <option value="">Choose…</option>
                        @foreach ($trainers as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="slot" class="label">Time slot <span class="font-normal text-ink-600">({{ auth()->user()->timezone ?? 'UTC' }})</span></label>
                    <select id="slot" wire:model="slot" class="input mt-1">
                        <option value="">Choose…</option>
                        @foreach ($openSlots as $s)
                            <option value="{{ $s->toIso8601String() }}">
                                {{ $s->setTimezone(auth()->user()->timezone ?? 'UTC')->format('D, M j · g:i A') }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button wire:click="book" @disabled(! $trainerId || ! $slot) class="btn-primary mt-4">Book session</button>
        @endif
    </section>

    <section>
        <div class="register-rule"><h2 class="register shrink-0">Your sessions</h2></div>
        <div class="ledger mt-3">
            @forelse ($appointments as $appt)
                <div class="ledger-row">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-ink-900">{{ $appt->trainer->name }}</p>
                        <p class="text-xs text-ink-500">
                            {{ $appt->starts_at->setTimezone(auth()->user()->timezone ?? 'UTC')->format('M j, g:i A T') }}
                            · {{ ucfirst($appt->status) }}
                        </p>
                    </div>
                    @if ($appt->status === 'booked')
                        <button wire:click="cancel({{ $appt->id }})" wire:confirm="Cancel this session?"
                                class="shrink-0 text-sm font-medium text-red-700 hover:underline">Cancel</button>
                    @endif
                </div>
            @empty
                <x-ui.empty title="No sessions booked" />
            @endforelse
        </div>
    </section>
</div>
