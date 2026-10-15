<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ route('trainer.assignments', $assignment->course) }}" class="link text-sm">&larr; Assignments</a>
    <div>
        <p class="register">Grading · max {{ $assignment->max_points }} pts · due {{ $assignment->due_at?->format('M j, Y g:i A') ?? '—' }}</p>
        <h1 class="h1 mt-1.5">{{ $assignment->title }}</h1>
        <div class="mt-4 h-px w-full bg-paper-300"></div>
    </div>

    @if (session('saved'))<div class="rounded-lg bg-emerald-50 px-4 py-2 text-sm text-emerald-800">{{ session('saved') }}</div>@endif

    <div class="space-y-4">
        @forelse ($submissions as $sub)
            <div class="card-pad">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-medium">{{ $sub->user->name }}</p>
                        <p class="text-xs text-ink-500">{{ ucfirst($sub->status) }} · submitted {{ $sub->submitted_at?->diffForHumans() }}</p>
                    </div>
                    @if ($sub->grade)
                        <span class="rounded-full bg-reg-100 px-3 py-1 text-sm font-semibold text-reg-700">
                            {{ $sub->grade->score }}/{{ $assignment->max_points }} · {{ ucfirst($sub->grade->status) }}
                        </span>
                    @endif
                </div>

                @if ($sub->body)
                    <div class="mt-3 rounded-lg bg-paper-50 p-3 text-sm">{{ $sub->body }}</div>
                @endif
                @if ($sub->attachments->isNotEmpty())
                    <ul class="mt-2 text-sm text-reg-700">
                        @foreach ($sub->attachments as $att)
                            <li><a href="{{ route('files.attachment', $att) }}" class="hover:underline">⭳ {{ $att->original_name }}</a></li>
                        @endforeach
                    </ul>
                @endif

                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <input type="number" wire:model="scores.{{ $sub->id }}" min="0" max="{{ $assignment->max_points }}"
                           placeholder="Score" value="{{ $sub->grade?->score }}" class="input">
                    <input wire:model="feedbacks.{{ $sub->id }}" placeholder="Private feedback"
                           value="{{ $sub->grade?->feedback }}" class="input sm:col-span-2">
                </div>
                <div class="mt-3 flex gap-2">
                    <button wire:click="grade({{ $sub->id }})" class="btn-primary btn-sm">Release grade</button>
                    <button wire:click="returnForRevision({{ $sub->id }})" class="rounded-lg px-4 py-1.5 text-sm text-ink-600 hover:text-ink-900">Return for revision</button>
                </div>
            </div>
        @empty
            <p class="rounded-2xl border border-dashed border-paper-300 bg-white p-8 text-center text-sm text-ink-500">No submissions yet.</p>
        @endforelse
    </div>
</div>
