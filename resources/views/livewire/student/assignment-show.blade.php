<div class="mx-auto max-w-3xl space-y-6">
    <a href="{{ route('learn.course', $enrolment) }}" class="text-sm text-eamn-700 hover:underline">&larr; Back to course</a>
    <h1 class="text-2xl font-semibold tracking-tight">{{ $assignment->title }}</h1>

    @if ($assignment->instructions)
        <div class="prose max-w-none rounded-xl border border-gray-200 bg-white p-5 text-sm">
            {!! nl2br(e($assignment->instructions)) !!}
        </div>
    @endif

    <div @class(['rounded-lg px-4 py-2 text-sm', 'bg-red-50 text-red-700' => $overdue, 'bg-gray-50 text-gray-600' => ! $overdue])>
        {{ $assignment->due_at ? 'Due '.$assignment->due_at->setTimezone(auth()->user()->timezone ?? 'UTC')->format('M j, Y g:i A T').($overdue ? ' — past due' : '') : 'No deadline' }}
        · Max {{ $assignment->max_points }} pts
    </div>

    @if (session('saved'))<div class="rounded-lg bg-green-50 px-4 py-2 text-sm text-green-700">{{ session('saved') }}</div>@endif

    <form wire:submit="save" class="space-y-4 rounded-xl border border-gray-200 bg-white p-5">
        <div>
            <label for="body" class="block text-sm font-medium">Your answer</label>
            <textarea id="body" wire:model="body" rows="8"
                      class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-eamn-500 focus:ring-1 focus:ring-eamn-500"></textarea>
        </div>
        <div>
            <label for="upload" class="block text-sm font-medium">Attach a file (optional)</label>
            <input id="upload" type="file" wire:model="upload" class="mt-1 text-sm">
            @error('upload')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <button type="submit" wire:loading.attr="disabled"
                class="rounded-lg bg-eamn-700 px-5 py-2 text-sm font-semibold text-white hover:bg-eamn-800">
            Submit work
        </button>
    </form>

    @if ($submission->attachments->isNotEmpty())
        <section>
            <h2 class="text-sm font-medium">Your files</h2>
            <ul class="mt-1 text-sm text-eamn-700">
                @foreach ($submission->attachments as $att)
                    <li><a href="{{ route('files.attachment', $att) }}" class="hover:underline">⭳ {{ $att->original_name }}</a></li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($grade && $grade->status === 'released')
        <section class="rounded-xl border border-eamn-200 bg-eamn-50 p-5">
            <h2 class="font-semibold text-eamn-900">Feedback</h2>
            <p class="mt-1 text-2xl font-bold">{{ $grade->score }} / {{ $assignment->max_points }}</p>
            @if ($grade->feedback)<p class="mt-2 text-sm text-gray-700">{{ $grade->feedback }}</p>@endif
        </section>
    @elseif ($grade)
        <p class="text-sm text-gray-500">Graded — awaiting release.</p>
    @endif
</div>
