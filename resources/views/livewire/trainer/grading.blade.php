<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ route('trainer.assignments', $assignment->course) }}" class="text-sm text-eamn-700 hover:underline">&larr; Assignments</a>
    <h1 class="h1">{{ $assignment->title }} — grading</h1>
    <p class="text-sm text-gray-500">Max {{ $assignment->max_points }} pts · due {{ $assignment->due_at?->format('M j, Y g:i A') ?? '—' }}</p>

    @if (session('saved'))<div class="rounded-lg bg-green-50 px-4 py-2 text-sm text-green-700">{{ session('saved') }}</div>@endif

    <div class="space-y-4">
        @forelse ($submissions as $sub)
            <div class="card-pad">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-medium">{{ $sub->user->name }}</p>
                        <p class="text-xs text-gray-500">{{ ucfirst($sub->status) }} · submitted {{ $sub->submitted_at?->diffForHumans() }}</p>
                    </div>
                    @if ($sub->grade)
                        <span class="rounded-full bg-eamn-100 px-3 py-1 text-sm font-semibold text-eamn-800">
                            {{ $sub->grade->score }}/{{ $assignment->max_points }} · {{ ucfirst($sub->grade->status) }}
                        </span>
                    @endif
                </div>

                @if ($sub->body)
                    <div class="mt-3 rounded-lg bg-gray-50 p-3 text-sm">{{ $sub->body }}</div>
                @endif
                @if ($sub->attachments->isNotEmpty())
                    <ul class="mt-2 text-sm text-eamn-700">
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
                    <button wire:click="returnForRevision({{ $sub->id }})" class="rounded-lg px-4 py-1.5 text-sm text-gray-600 hover:text-gray-900">Return for revision</button>
                </div>
            </div>
        @empty
            <p class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500">No submissions yet.</p>
        @endforelse
    </div>
</div>
