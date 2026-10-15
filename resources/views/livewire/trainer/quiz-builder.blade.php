<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ route('trainer.quizzes', $quiz->course) }}" class="text-sm text-eamn-700 hover:underline">&larr; Quizzes</a>
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold tracking-tight">{{ $quiz->title }}</h1>
        @if ($quiz->status === 'published')
            <button wire:click="unpublish" class="rounded-lg border border-gray-300 px-4 py-2 text-sm">Unpublish</button>
        @else
            <button wire:click="publish" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white">Publish</button>
        @endif
    </div>

    @if (session('saved'))<div class="rounded-lg bg-green-50 px-4 py-2 text-sm text-green-700">{{ session('saved') }}</div>@endif

    <section class="rounded-xl border border-gray-200 bg-white p-5">
        <h2 class="font-semibold">Settings</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-3">
            <label class="text-sm">Time limit (min, blank=none)
                <input type="number" wire:model="timeLimit" min="1" class="mt-1 block w-full rounded-lg border-gray-300"></label>
            <label class="text-sm">Max attempts
                <input type="number" wire:model="maxAttempts" min="1" class="mt-1 block w-full rounded-lg border-gray-300"></label>
            <label class="text-sm">Passing score
                <input type="number" wire:model="passingScore" min="0" class="mt-1 block w-full rounded-lg border-gray-300"></label>
            <label class="text-sm">Release results
                <select wire:model="releaseResults" class="mt-1 block w-full rounded-lg border-gray-300">
                    <option value="immediate">Immediate</option>
                    <option value="after_close">After close</option>
                    <option value="manual">Manual</option>
                </select></label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="randomiseQuestions" class="rounded"> Randomise questions</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="randomiseOptions" class="rounded"> Randomise options</label>
        </div>
        <button wire:click="saveSettings" class="mt-4 rounded-lg bg-eamn-700 px-4 py-2 text-sm font-semibold text-white">Save settings</button>
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-5">
        <h2 class="font-semibold">Questions</h2>
        <ul class="mt-3 divide-y divide-gray-100">
            @forelse ($questions as $q)
                <li class="flex items-start justify-between py-3">
                    <div>
                        <p class="text-sm font-medium">{{ $q->prompt }}</p>
                        <p class="text-xs text-gray-500">{{ strtoupper($q->type) }} · {{ $q->points }}pt · {{ $q->options->count() }} options</p>
                    </div>
                    <span class="flex gap-1">
                        <button wire:click="moveQuestion({{ $q->id }}, 'up')" class="px-1 text-gray-500">↑</button>
                        <button wire:click="moveQuestion({{ $q->id }}, 'down')" class="px-1 text-gray-500">↓</button>
                        <button wire:click="deleteQuestion({{ $q->id }})" class="px-1 text-red-600">✕</button>
                    </span>
                </li>
            @empty
                <li class="py-4 text-sm text-gray-500">No questions yet.</li>
            @endforelse
        </ul>
    </section>

    <section class="rounded-xl border border-eamn-200 bg-eamn-50 p-5">
        <h3 class="font-semibold">Add question</h3>
        <div class="mt-3 space-y-3">
            <div class="grid gap-3 sm:grid-cols-3">
                <select wire:model.live="qType" class="rounded-lg border-gray-300 text-sm">
                    <option value="mcq">Multiple choice</option><option value="multi">Multiple response</option>
                    <option value="tf">True/False</option><option value="short">Short answer</option><option value="essay">Essay</option>
                </select>
                <input type="number" wire:model="qPoints" min="1" placeholder="Points" class="rounded-lg border-gray-300 text-sm">
            </div>
            <input wire:model="qPrompt" placeholder="Question prompt" class="block w-full rounded-lg border-gray-300 text-sm">

            @if (in_array($qType, ['mcq','multi','tf','short']))
                <div class="space-y-2">
                    <p class="text-xs text-gray-500">
                        {{ $qType === 'short' ? 'Acceptable answers (case-insensitive):' : 'Options — mark correct:' }}
                    </p>
                    @foreach ($qOptions as $i => $opt)
                        <div class="flex items-center gap-2">
                            @if ($qType !== 'short')
                                <input type="checkbox" wire:model="qOptions.{{ $i }}.correct" class="rounded text-eamn-600">
                            @endif
                            <input wire:model="qOptions.{{ $i }}.label" placeholder="Option {{ $i + 1 }}" class="flex-1 rounded-lg border-gray-300 text-sm">
                        </div>
                    @endforeach
                    <button type="button" wire:click="addOption" class="text-sm text-eamn-700">+ Add option</button>
                </div>
            @endif

            <button wire:click="addQuestion" class="rounded-lg bg-eamn-700 px-4 py-2 text-sm font-semibold text-white">Add question</button>
        </div>
    </section>
</div>
