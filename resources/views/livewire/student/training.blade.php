<div class="mx-auto max-w-3xl space-y-8">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold tracking-tight">Private training</h1>
        <span class="rounded-full bg-eamn-100 px-4 py-1.5 text-sm font-semibold text-eamn-800">
            {{ $credits }} credit{{ $credits === 1 ? '' : 's' }}
        </span>
    </div>

    @if (session('booked'))<div class="rounded-lg bg-green-50 px-4 py-2 text-sm text-green-700">{{ session('booked') }}</div>@endif

    <section class="rounded-xl border border-gray-200 bg-white p-5">
        <h2 class="font-semibold">Book a session</h2>
        @if ($credits < 1)
            <p class="mt-2 text-sm text-gray-500">No session credits. Purchase a package to book.</p>
        @else
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium">Trainer</label>
                    <select wire:model.live="trainerId" class="mt-1 block w-full rounded-lg border-gray-300">
                        <option value="">Choose…</option>
                        @foreach ($trainers as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium">Time slot (your timezone: {{ auth()->user()->timezone ?? 'UTC' }})</label>
                    <select wire:model="slot" class="mt-1 block w-full rounded-lg border-gray-300">
                        <option value="">Choose…</option>
                        @foreach ($openSlots as $s)
                            <option value="{{ $s->toIso8601String() }}">
                                {{ $s->setTimezone(auth()->user()->timezone ?? 'UTC')->format('D, M j · g:i A') }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button wire:click="book" @disabled(! $trainerId || ! $slot)
                    class="mt-4 rounded-lg bg-eamn-700 px-5 py-2 text-sm font-semibold text-white hover:bg-eamn-800 disabled:opacity-50">
                Book session
            </button>
        @endif
    </section>

    <section>
        <h2 class="text-lg font-semibold">Your sessions</h2>
        <div class="mt-3 space-y-2">
            @forelse ($appointments as $appt)
                <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-white p-4">
                    <div>
                        <p class="font-medium text-sm">{{ $appt->trainer->name }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $appt->starts_at->setTimezone(auth()->user()->timezone ?? 'UTC')->format('M j, g:i A T') }}
                            · {{ ucfirst($appt->status) }}
                        </p>
                    </div>
                    @if ($appt->status === 'booked')
                        <button wire:click="cancel({{ $appt->id }})" wire:confirm="Cancel this session?"
                                class="text-sm text-red-600 hover:underline">Cancel</button>
                    @endif
                </div>
            @empty
                <p class="rounded-xl border border-dashed border-gray-300 bg-white p-6 text-center text-sm text-gray-500">No sessions booked.</p>
            @endforelse
        </div>
    </section>
</div>
