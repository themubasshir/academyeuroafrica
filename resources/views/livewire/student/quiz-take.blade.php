<div class="mx-auto max-w-3xl space-y-6">
    <a href="{{ route('learn.course', $enrolment) }}" class="text-sm text-eamn-700 hover:underline">&larr; Back to course</a>
    <h1 class="text-2xl font-semibold tracking-tight">{{ $quiz->title }}</h1>

    @if ($quiz->instructions)
        <p class="text-gray-600">{{ $quiz->instructions }}</p>
    @endif

    @if ($attempt === null)
        <div class="rounded-xl border border-gray-200 bg-white p-8 text-center">
            <p class="text-gray-600">
                {{ $quiz->time_limit_minutes ? "Time limit: {$quiz->time_limit_minutes} min." : 'No time limit.' }}
                {{ "Up to {$quiz->max_attempts} attempt(s)." }}
            </p>
            <button wire:click="start"
                    class="mt-4 rounded-lg bg-eamn-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-eamn-800">
                Start quiz
            </button>
        </div>
    @elseif ($finished)
        <div class="rounded-xl border border-green-200 bg-green-50 p-8 text-center">
            <h2 class="text-xl font-semibold text-green-800">Submitted</h2>
            @if ($result && $result->score !== null)
                <p class="mt-2 text-3xl font-bold text-green-900">{{ $result->score }} pts</p>
            @else
                <p class="mt-2 text-sm text-green-700">Awaiting manual grading.</p>
            @endif
        </div>
    @else
        <div class="rounded-lg bg-amber-50 px-4 py-2 text-sm text-amber-800">
            In progress @if($quiz->time_limit_minutes) — started {{ $attempt->started_at->diffForHumans() }}@endif
        </div>

        <form wire:submit="submit" class="space-y-6">
            @foreach ($questions as $question)
                <fieldset class="rounded-xl border border-gray-200 bg-white p-5">
                    <legend class="px-1 text-sm font-medium">{{ $question->prompt }}
                        <span class="text-gray-400">({{ $question->points }}pt)</span></legend>

                    @if (in_array($question->type, ['mcq','tf']))
                        <div class="mt-3 space-y-2">
                            @foreach ($question->options as $option)
                                <label class="flex items-center gap-3 text-sm">
                                    <input type="radio" name="q{{ $question->id }}" value="{{ $option->id }}"
                                           wire:model="responses.{{ $question->id }}"
                                           class="h-4 w-4 text-eamn-600 focus:ring-eamn-500">
                                    {{ $option->label }}
                                </label>
                            @endforeach
                        </div>
                    @elseif ($question->type === 'multi')
                        <div class="mt-3 space-y-2">
                            @foreach ($question->options as $option)
                                <label class="flex items-center gap-3 text-sm">
                                    <input type="checkbox" value="{{ $option->id }}"
                                           wire:model="responses.{{ $question->id }}.{{ $option->id }}"
                                           class="h-4 w-4 rounded text-eamn-600 focus:ring-eamn-500">
                                    {{ $option->label }}
                                </label>
                            @endforeach
                        </div>
                    @else
                        <textarea wire:model="responses.{{ $question->id }}" rows="{{ $question->type === 'essay' ? 6 : 2 }}"
                                  class="mt-3 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-eamn-500 focus:ring-1 focus:ring-eamn-500"></textarea>
                    @endif
                </fieldset>
            @endforeach

            <button type="submit"
                    class="rounded-lg bg-eamn-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-eamn-800">
                Submit answers
            </button>
        </form>
    @endif
</div>
