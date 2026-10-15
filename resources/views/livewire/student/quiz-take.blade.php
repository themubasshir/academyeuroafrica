<div class="mx-auto max-w-3xl space-y-6">
    <a href="{{ route('learn.course', $enrolment) }}" class="text-sm font-medium text-eamn-700 hover:underline">&larr; Back to course</a>
    <h1 class="h1">{{ $quiz->title }}</h1>

    @if ($quiz->instructions)
        <p class="card-pad text-sm text-gray-600">{{ $quiz->instructions }}</p>
    @endif

    @if ($attempt === null)
        <div class="card-pad text-center">
            <p class="text-sm text-gray-600">
                {{ $quiz->time_limit_minutes ? "Time limit: {$quiz->time_limit_minutes} min." : 'No time limit.' }}
                {{ "Up to {$quiz->max_attempts} attempt(s)." }}
            </p>
            <button wire:click="start" class="btn-primary mt-5">Start quiz</button>
        </div>
    @elseif ($finished)
        <div class="card-pad border-green-200 bg-green-50 text-center">
            <h2 class="text-xl font-semibold text-green-800">Submitted</h2>
            @if ($result && $result->score !== null)
                <p class="mt-3 text-4xl font-bold text-green-900">{{ $result->score }} <span class="h2">pts</span></p>
            @else
                <p class="mt-2 text-sm text-green-700">Awaiting manual grading.</p>
            @endif
            <a href="{{ route('student.results') }}" class="btn-secondary mt-5">View my results</a>
        </div>
    @else
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-medium text-amber-800" role="status">
            In progress @if($quiz->time_limit_minutes) — started {{ $attempt->started_at->diffForHumans() }}@endif
        </div>

        <form wire:submit="submit" class="space-y-6">
            @foreach ($questions as $idx => $question)
                <fieldset class="card-pad">
                    <legend class="px-1 text-sm font-semibold text-gray-900">
                        Q{{ $idx + 1 }}. {{ $question->prompt }}
                        <span class="font-normal text-gray-400">({{ $question->points }}pt)</span>
                    </legend>

                    @if (in_array($question->type, ['mcq','tf']))
                        <div class="mt-3 space-y-2">
                            @foreach ($question->options as $option)
                                <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 px-3 py-2.5 text-sm transition hover:border-eamn-300 has-[:checked]:border-eamn-600 has-[:checked]:bg-eamn-50">
                                    <input type="radio" name="q{{ $question->id }}" value="{{ $option->id }}"
                                           wire:model="responses.{{ $question->id }}"
                                           class="h-4 w-4 shrink-0 text-eamn-600 focus:ring-eamn-500">
                                    {{ $option->label }}
                                </label>
                            @endforeach
                        </div>
                    @elseif ($question->type === 'multi')
                        <div class="mt-3 space-y-2">
                            @foreach ($question->options as $option)
                                <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 px-3 py-2.5 text-sm transition hover:border-eamn-300 has-[:checked]:border-eamn-600 has-[:checked]:bg-eamn-50">
                                    <input type="checkbox" value="{{ $option->id }}"
                                           wire:model="responses.{{ $question->id }}.{{ $option->id }}"
                                           class="h-4 w-4 shrink-0 rounded text-eamn-600 focus:ring-eamn-500">
                                    {{ $option->label }}
                                </label>
                            @endforeach
                        </div>
                    @else
                        <textarea wire:model="responses.{{ $question->id }}" rows="{{ $question->type === 'essay' ? 6 : 2 }}"
                                  class="input mt-3"></textarea>
                    @endif
                </fieldset>
            @endforeach

            <button type="submit" wire:loading.attr="disabled" class="btn-primary">
                <span wire:loading.remove wire:target="submit">Submit answers</span>
                <span wire:loading wire:target="submit">Submitting…</span>
            </button>
        </form>
    @endif
</div>
