<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ route('trainer.dashboard') }}" class="text-sm link">&larr; Dashboard</a>
    <div>
        <div class="mt-3"><p class="register">{{ $course->programme->name }}</p><h1 class="h1 mt-1.5">{{ $course->title }}</h1></div>
    </div>
    <x-trainer.course-nav :course="$course" active="assignments" />

    @if (session('saved'))<div class="rounded-lg bg-emerald-50 px-4 py-2 text-sm text-emerald-800">{{ session('saved') }}</div>@endif

    <section class="card-pad">
        <h2 class="font-semibold">New assignment</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <input wire:model="title" placeholder="Title" class="input sm:col-span-2">
            <textarea wire:model="instructions" placeholder="Instructions" rows="3" class="input sm:col-span-2"></textarea>
            <input type="datetime-local" wire:model="dueAt" class="input" title="Due date">
            <input type="number" wire:model="maxPoints" min="1" class="input" placeholder="Max points">
            <select wire:model.live="audience" class="input">
                <option value="course">All enrolled</option><option value="group">A group</option><option value="students">Specific students</option>
            </select>
            @if ($audience === 'group')
                <select wire:model="groupId" class="input">
                    <option value="">Group…</option>
                    @foreach ($groups as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach
                </select>
            @elseif ($audience === 'students')
                <div class="max-h-32 space-y-1 overflow-y-auto rounded-lg border border-paper-200 p-2 sm:col-span-2">
                    @foreach ($students as $s)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="recipientIds" value="{{ $s->id }}" class="rounded text-reg-600">
                            {{ $s->name }}
                        </label>
                    @endforeach
                </div>
            @endif
        </div>
        <button wire:click="create" class="mt-4 btn-primary">Create</button>
    </section>

    <section>
        <h2 class="h2">Assignments</h2>
        <div class="mt-3 space-y-2">
            @forelse ($assignments as $a)
                <a href="{{ route('trainer.grading', $a) }}"
                   class="flex items-center justify-between card p-4 hover:border-reg-500/60">
                    <div>
                        <p class="font-medium text-sm">{{ $a->title }}</p>
                        <p class="text-xs text-ink-500">{{ $a->submissions_count }} submission(s) · {{ ucfirst($a->audience) }}</p>
                    </div>
                    <span class="text-sm text-ink-500">{{ $a->due_at?->format('M j') ?? '—' }}</span>
                </a>
            @empty
                <p class="rounded-2xl border border-dashed border-paper-300 bg-white p-6 text-center text-sm text-ink-500">No assignments.</p>
            @endforelse
        </div>
    </section>
</div>
