<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ route('trainer.quizzes', $quiz->course) }}" class="link text-sm">&larr; Quizzes</a>
    <div class="flex items-end justify-between gap-3">
        <div><p class="register">Quiz builder</p><h1 class="h1 mt-1.5">{{ $quiz->title }}</h1></div>
        @if ($quiz->status === 'published')
            <button wire:click="unpublish" class="btn-secondary btn-sm">Unpublish</button>
        @else
            <button wire:click="publish" class="btn bg-green-600 text-white hover:bg-green-700">Publish</button>
        @endif
    </div>

    @if (session('saved'))<div class="rounded-lg bg-emerald-50 px-4 py-2 text-sm text-emerald-800">{{ session('saved') }}</div>@endif

    <section class="card-pad">
        <h2 class="font-semibold">Settings</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-3">
            <label class="text-sm">Time limit (min, blank=none)
                <input type="number" wire:model="timeLimit" min="1" class="input mt-1"></label>
            <label class="text-sm">Max attempts
                <input type="number" wire:model="maxAttempts" min="1" class="input mt-1"></label>
            <label class="text-sm">Passing score (points)
                <input type="number" wire:model="passingScore" min="0" class="input mt-1"></label>
            <label class="text-sm">Release results
                <select wire:model="releaseResults" class="input mt-1">
                    <option value="immediate">Immediate</option>
                    <option value="after_close">After close</option>
                    <option value="manual">Manual</option>
                </select></label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="randomiseQuestions" class="rounded"> Randomise questions</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="randomiseOptions" class="rounded"> Randomise options</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="isRequired" class="rounded"> Required for certificate</label>
        </div>
        <button wire:click="saveSettings" class="mt-4 btn-primary">Save settings</button>
    </section>

    <section class="card-pad">
        <h2 class="font-semibold">Questions</h2>
        <ul class="mt-3 divide-y divide-paper-200">
            @forelse ($questions as $q)
                <li class="flex items-start justify-between py-3">
                    <div>
                        <p class="text-sm font-medium">{{ $q->prompt }}</p>
                        <p class="text-xs text-ink-500">{{ strtoupper($q->type) }} · {{ $q->points }}pt · {{ $q->options->count() }} options</p>
                    </div>
                    <span class="flex gap-1">
                        <button wire:click="moveQuestion({{ $q->id }}, 'up')" class="flex h-11 w-11 items-center justify-center rounded-md text-ink-500 hover:bg-paper-100" aria-label="Move question up">↑</button>
                        <button wire:click="moveQuestion({{ $q->id }}, 'down')" class="flex h-11 w-11 items-center justify-center rounded-md text-ink-500 hover:bg-paper-100" aria-label="Move question down">↓</button>
                        <button wire:click="deleteQuestion({{ $q->id }})" class="flex h-11 w-11 items-center justify-center rounded-md text-red-700 hover:bg-red-50" aria-label="Delete question">✕</button>
                    </span>
                </li>
            @empty
                <li class="py-4 text-sm text-ink-500">No questions yet.</li>
            @endforelse
        </ul>
    </section>

    <section class="rounded-lg border border-reg-100 bg-reg-50/60 p-5">
        <h3 class="font-semibold">Add question</h3>
        <div class="mt-3 space-y-3">
            <div class="grid gap-3 sm:grid-cols-3">
                <select wire:model.live="qType" class="input">
                    <option value="mcq">Multiple choice</option><option value="multi">Multiple response</option>
                    <option value="tf">True/False</option><option value="short">Short answer</option><option value="essay">Essay</option>
                </select>
                <input type="number" wire:model="qPoints" min="1" placeholder="Points" class="input">
            </div>
            <input wire:model="qPrompt" placeholder="Question prompt" class="block w-full input">

            @if (in_array($qType, ['mcq','multi','tf','short']))
                <div class="space-y-2">
                    <p class="text-xs text-ink-500">
                        {{ $qType === 'short' ? 'Acceptable answers (case-insensitive):' : 'Options — mark correct:' }}
                    </p>
                    @foreach ($qOptions as $i => $opt)
                        <div class="flex items-center gap-2">
                            @if ($qType !== 'short')
                                <input type="checkbox" wire:model="qOptions.{{ $i }}.correct" class="rounded text-reg-600">
                            @endif
                            <input wire:model="qOptions.{{ $i }}.label" placeholder="Option {{ $i + 1 }}" class="flex-1 input">
                        </div>
                    @endforeach
                    <button type="button" wire:click="addOption" class="text-sm text-reg-700">+ Add option</button>
                </div>
            @endif

            <button wire:click="addQuestion" class="btn-primary">Add question</button>
        </div>
    </section>
</div>
