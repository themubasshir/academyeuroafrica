<div class="mx-auto max-w-3xl space-y-6">
    <a href="{{ route('learn.course', $enrolment) }}" class="text-sm font-medium text-eamn-700 hover:underline">&larr; Back to course</a>

    <div class="flex flex-wrap items-start justify-between gap-3">
        <h1 class="h1">{{ $assignment->title }}</h1>
        <span @class(['badge', 'badge-red' => $overdue, 'badge-gray' => ! $overdue])>
            {{ $assignment->due_at ? 'Due '.$assignment->due_at->setTimezone(auth()->user()->timezone ?? 'UTC')->format('M j, g:i A').($overdue ? ' · past due' : '') : 'No deadline' }}
        </span>
    </div>
    <p class="muted -mt-3">Max {{ $assignment->max_points }} pts</p>

    @if ($assignment->instructions)
        <div class="card-pad prose max-w-none text-sm text-gray-700">
            {!! nl2br(e($assignment->instructions)) !!}
        </div>
    @endif

    <form wire:submit="save" class="card-pad space-y-5">
        <div>
            <label for="body" class="label">Your answer</label>
            <textarea id="body" wire:model="body" rows="8" class="input"></textarea>
        </div>
        <div>
            <label for="upload" class="label">Attach a file (optional)</label>
            <input id="upload" type="file" wire:model="upload" class="mt-1 text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-eamn-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-eamn-700">
            @error('upload')<p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
        </div>
        <button type="submit" wire:loading.attr="disabled" class="btn-primary">
            <span wire:loading.remove wire:target="save">Submit work</span>
            <span wire:loading wire:target="save">Submitting…</span>
        </button>
    </form>

    @if ($submission->attachments->isNotEmpty())
        <section>
            <h2 class="h2">Your files</h2>
            <ul class="card mt-3 divide-y divide-gray-100">
                @foreach ($submission->attachments as $att)
                    <li><a href="{{ route('files.attachment', $att) }}" class="flex items-center gap-2 px-4 py-3 text-sm font-medium text-eamn-700 hover:bg-eamn-50">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"/></svg>
                        {{ $att->original_name }}</a></li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($grade && $grade->status === 'released')
        <section class="card-pad border-eamn-200 bg-eamn-50">
            <h2 class="font-semibold text-eamn-900">Feedback</h2>
            <p class="mt-1 text-3xl font-bold text-eamn-800">{{ $grade->score }} <span class="text-lg font-medium text-gray-500">/ {{ $assignment->max_points }}</span></p>
            @if ($grade->feedback)<p class="mt-3 border-t border-eamn-100 pt-3 text-sm text-gray-700">{{ $grade->feedback }}</p>@endif
        </section>
    @elseif ($grade)
        <p class="badge-amber">Graded — awaiting release</p>
    @endif
</div>
