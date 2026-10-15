<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ route('trainer.dashboard') }}" class="text-sm text-eamn-700 hover:underline">&larr; Dashboard</a>
    <h1 class="text-2xl font-semibold tracking-tight">Live sessions — {{ $course->title }}</h1>

    @if (session('saved'))<div class="rounded-lg bg-green-50 px-4 py-2 text-sm text-green-700">{{ session('saved') }}</div>@endif

    <section class="rounded-xl border border-gray-200 bg-white p-5">
        <h2 class="font-semibold">Schedule session</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <input wire:model="title" placeholder="Title" class="rounded-lg border-gray-300 text-sm sm:col-span-2">
            <input type="datetime-local" wire:model="startsAt" class="rounded-lg border-gray-300 text-sm">
            <input type="number" wire:model="duration" min="15" step="15" class="rounded-lg border-gray-300 text-sm" placeholder="Minutes">
            <select wire:model="provider" class="rounded-lg border-gray-300 text-sm">
                <option value="zoom">Zoom</option><option value="teams">Microsoft Teams</option>
            </select>
            <input wire:model="meetingUrl" placeholder="Meeting URL" class="rounded-lg border-gray-300 text-sm">
            <select wire:model.live="audience" class="rounded-lg border-gray-300 text-sm">
                <option value="course">Whole course</option><option value="group">A group</option><option value="student">One student</option>
            </select>
            @if ($audience === 'group')
                <select wire:model="groupId" class="rounded-lg border-gray-300 text-sm">
                    <option value="">Group…</option>
                    @foreach ($groups as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach
                </select>
            @elseif ($audience === 'student')
                <select wire:model="studentId" class="rounded-lg border-gray-300 text-sm">
                    <option value="">Student…</option>
                    @foreach ($students as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                </select>
            @endif
        </div>
        <button wire:click="save" class="mt-4 rounded-lg bg-eamn-700 px-4 py-2 text-sm font-semibold text-white">Schedule</button>
    </section>

    <section>
        <h2 class="text-lg font-semibold">Upcoming</h2>
        <div class="mt-3 space-y-2">
            @forelse ($sessions->where('status','scheduled')->where('starts_at','>',now()) as $s)
                <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-white p-4">
                    <div>
                        <p class="font-medium text-sm">{{ $s->title }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $s->starts_at->setTimezone(auth()->user()->timezone ?? 'UTC')->format('M j, g:i A T') }}
                            · {{ ucfirst($s->provider) }}
                            @if ($s->group)· Group: {{ $s->group->name }}@endif
                            @if ($s->student)· 1:1 {{ $s->student->name }}@endif
                        </p>
                    </div>
                    <button wire:click="cancel({{ $s->id }})" class="text-sm text-red-600">Cancel</button>
                </div>
            @empty
                <p class="rounded-xl border border-dashed border-gray-300 bg-white p-6 text-center text-sm text-gray-500">No sessions.</p>
            @endforelse
        </div>
    </section>
</div>
