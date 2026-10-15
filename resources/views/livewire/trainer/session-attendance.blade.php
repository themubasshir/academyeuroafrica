<div class="mx-auto max-w-3xl space-y-6">
    <a href="{{ route('trainer.sessions', $session->course) }}" class="link text-sm">&larr; Sessions</a>
    <x-ui.page-head :register="$session->course->title" :title="'Attendance — '.$session->title" :folio="$session->starts_at->format('j M Y · g:i A')" />

    @if (session('saved'))<div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-800">{{ session('saved') }}</div>@endif

    <form wire:submit="save" class="card-pad">
        <ul class="divide-y divide-paper-200">
            @forelse ($roster as $user)
                <li class="flex items-center justify-between gap-4 py-3">
                    <span class="text-sm font-medium text-ink-900">{{ $user->name }}</span>
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-ink-700">
                        <input type="checkbox" wire:model="attendance.{{ $user->id }}" @checked($attendance[$user->id] ?? false)
                               class="h-4 w-4 rounded border-paper-300 text-reg-600 focus:ring-reg-500">
                        Attended
                    </label>
                </li>
            @empty
                <li class="py-6 text-center text-sm text-ink-500">No eligible attendees.</li>
            @endforelse
        </ul>
        <button class="btn-primary mt-5">Save attendance</button>
    </form>
</div>
