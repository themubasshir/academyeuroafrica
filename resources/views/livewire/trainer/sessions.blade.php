<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ route('trainer.dashboard') }}" class="text-sm text-eamn-700 hover:underline">&larr; Dashboard</a>
    <h1 class="h1">Live sessions — {{ $course->title }}</h1>

    @if (session('saved'))<div class="rounded-lg bg-green-50 px-4 py-2 text-sm text-green-700">{{ session('saved') }}</div>@endif

    <section class="card-pad">
        <h2 class="font-semibold">Schedule session</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2"><label for="t" class="label">Title</label><input id="t" wire:model="title" class="input mt-1"></div>
            <div><label for="sa" class="label">Starts at</label><input id="sa" type="datetime-local" wire:model="startsAt" class="input mt-1"></div>
            <div><label for="du" class="label">Minutes</label><input id="du" type="number" wire:model="duration" min="15" step="15" class="input mt-1"></div>
            <div><label for="pv" class="label">Provider</label>
                <select id="pv" wire:model="provider" class="input mt-1"><option value="zoom">Zoom</option><option value="teams">Microsoft Teams</option></select></div>
            <div><label for="mu" class="label">Meeting URL</label><input id="mu" wire:model="meetingUrl" class="input mt-1"></div>
            <div><label for="au" class="label">Audience</label>
                <select id="au" wire:model.live="audience" class="input mt-1">
                    <option value="course">Whole course</option><option value="group">A group</option><option value="student">One student</option>
                </select></div>
            @if ($audience === 'group')
                <div><label for="gr" class="label">Group</label>
                    <select id="gr" wire:model="groupId" class="input mt-1">
                        <option value="">Group…</option>
                        @foreach ($groups as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach
                    </select></div>
            @elseif ($audience === 'student')
                <div><label for="stu" class="label">Student</label>
                    <select id="stu" wire:model="studentId" class="input mt-1">
                        <option value="">Student…</option>
                        @foreach ($students as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                    </select></div>
            @endif
        </div>
        <button wire:click="save" class="btn-primary mt-4">Schedule</button>
    </section>

    <section>
        <h2 class="h2">Upcoming</h2>
        <div class="mt-3 space-y-2">
            @forelse ($sessions->where('status','scheduled')->where('starts_at','>',now()) as $s)
                <div class="flex items-center justify-between card p-4">
                    <div>
                        <p class="font-medium text-sm">{{ $s->title }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $s->starts_at->setTimezone(auth()->user()->timezone ?? 'UTC')->format('M j, g:i A T') }}
                            · {{ ucfirst($s->provider) }}
                            @if ($s->group)· Group: {{ $s->group->name }}@endif
                            @if ($s->student)· 1:1 {{ $s->student->name }}@endif
                        </p>
                    </div>
                    <button wire:click="cancel({{ $s->id }})" wire:confirm="Cancel this session?" class="text-sm font-medium text-red-600 hover:underline">Cancel</button>
                </div>
            @empty
                <x-ui.empty title="No sessions" />
            @endforelse
        </div>
    </section>
</div>
