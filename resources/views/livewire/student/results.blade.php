<div class="mx-auto max-w-4xl space-y-8">
    <x-ui.page-head register="Student register" title="My results" :folio="'Ref EAMN-'.auth()->id()" />
    <p class="muted">Private to you. Released grades and feedback only.</p>

    <section aria-labelledby="quiz-res-heading">
        <div class="register-rule"><h2 id="quiz-res-heading" class="register shrink-0">Quizzes</h2></div>
        <div class="ledger mt-4">
            @forelse ($quizAttempts as $attempt)
                <div class="ledger-row">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-ink-900">{{ $attempt->quiz->title }}</p>
                        <p class="text-xs text-ink-500">{{ $attempt->quiz->course->title }} · attempt {{ $attempt->attempt_no }}</p>
                    </div>
                    <div class="text-right">
                        @if ($attempt->score !== null)
                            <p class="font-display text-2xl font-semibold text-ink-950">{{ $attempt->score }}</p>
                        @else
                            <span class="badge-amber">Pending</span>
                        @endif
                    </div>
                </div>
            @empty
                <x-ui.empty title="No quiz results yet" />
            @endforelse
        </div>
    </section>

    <section aria-labelledby="asg-res-heading">
        <div class="register-rule"><h2 id="asg-res-heading" class="register shrink-0">Assignments</h2></div>
        <div class="ledger mt-4">
            @forelse ($submissions as $submission)
                <div class="px-5 py-4">
                    <div class="flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-ink-900">{{ $submission->assignment->title }}</p>
                            <p class="text-xs text-ink-500">{{ $submission->assignment->course->title }}</p>
                        </div>
                        <div class="shrink-0 text-right">
                            @if ($submission->grade?->status === 'released')
                                <p class="font-display text-2xl font-semibold text-ink-950">{{ $submission->grade->score }}<span class="text-sm font-medium text-ink-600">/{{ $submission->assignment->max_points }}</span></p>
                            @else
                                <span class="badge-gray">{{ ucfirst($submission->status) }}</span>
                            @endif
                        </div>
                    </div>
                    @if ($submission->grade?->status === 'released' && $submission->grade->feedback)
                        <div class="mt-3 rounded-md border border-reg-100 bg-reg-50 px-4 py-3 text-sm text-ink-700">
                            <p class="register !text-reg-700">Trainer feedback</p>
                            <p class="mt-1.5">{{ $submission->grade->feedback }}</p>
                        </div>
                    @endif
                </div>
            @empty
                <x-ui.empty title="No submissions yet" />
            @endforelse
        </div>
    </section>
</div>
