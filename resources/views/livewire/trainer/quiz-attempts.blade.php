<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ route('trainer.quizzes', $quiz->course) }}" class="text-sm text-eamn-700 hover:underline">&larr; Quizzes</a>
    <h1 class="text-2xl font-semibold tracking-tight">{{ $quiz->title }} — attempts</h1>

    <div class="space-y-4">
        @forelse ($attempts as $attempt)
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium">{{ $attempt->user->name }}</p>
                        <p class="text-xs text-gray-500">Attempt {{ $attempt->attempt_no }} · {{ ucfirst(str_replace('_',' ',$attempt->status)) }} · {{ $attempt->submitted_at?->diffForHumans() }}</p>
                    </div>
                    <span class="text-xl font-semibold">{{ $attempt->score ?? '—' }}</span>
                </div>

                @if ($attempt->status === 'submitted')
                    <div class="mt-3 space-y-3 rounded-lg bg-amber-50 p-3">
                        <p class="text-xs font-semibold text-amber-800">Manual grading required</p>
                        @foreach ($attempt->answers->whereNull('awarded_points') as $ans)
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <div>
                                    <p class="font-medium">{{ $ans->question->prompt }}</p>
                                    <p class="text-gray-600">{{ $ans->text_answer }}</p>
                                </div>
                                <span class="flex items-center gap-1">
                                    <input type="number" min="0" max="{{ $ans->question->points }}"
                                           x-on:change="$wire.award({{ $ans->id }}, $event.target.value)"
                                           class="w-16 rounded border-gray-300 text-sm">
                                    / {{ $ans->question->points }}
                                </span>
                            </div>
                        @endforeach
                        <button wire:click="finalise({{ $attempt->id }})"
                                class="rounded-lg bg-eamn-700 px-4 py-1.5 text-sm font-semibold text-white">Finalise grade</button>
                    </div>
                @endif
            </div>
        @empty
            <p class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500">No attempts yet.</p>
        @endforelse
    </div>
</div>
