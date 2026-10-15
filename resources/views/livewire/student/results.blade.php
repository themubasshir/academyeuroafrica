<div class="mx-auto max-w-4xl space-y-8">
    <div>
        <h1 class="h1">My results</h1>
        <p class="muted mt-1">Private to you. Released grades and feedback only.</p>
    </div>

    <section aria-labelledby="quiz-res-heading">
        <h2 id="quiz-res-heading" class="h2">Quizzes</h2>
        <div class="card mt-4 divide-y divide-gray-100">
            @forelse ($quizAttempts as $attempt)
                <div class="flex items-center justify-between px-5 py-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ $attempt->quiz->title }}</p>
                        <p class="text-xs text-gray-500">{{ $attempt->quiz->course->title }} · attempt {{ $attempt->attempt_no }}</p>
                    </div>
                    <div class="text-right">
                        @if ($attempt->score !== null)
                            <p class="text-2xl font-bold text-eamn-800">{{ $attempt->score }}</p>
                        @else
                            <span class="badge-amber">Pending</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-6"><x-ui.empty title="No quiz results yet" /></div>
            @endforelse
        </div>
    </section>

    <section aria-labelledby="asg-res-heading">
        <h2 id="asg-res-heading" class="h2">Assignments</h2>
        <div class="card mt-4 divide-y divide-gray-100">
            @forelse ($submissions as $submission)
                <div class="px-5 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $submission->assignment->title }}</p>
                            <p class="text-xs text-gray-500">{{ $submission->assignment->course->title }}</p>
                        </div>
                        <div class="text-right">
                            @if ($submission->grade?->status === 'released')
                                <p class="text-2xl font-bold text-eamn-800">{{ $submission->grade->score }}<span class="text-sm font-medium text-gray-400">/{{ $submission->assignment->max_points }}</span></p>
                            @else
                                <span class="badge-gray">{{ ucfirst($submission->status) }}</span>
                            @endif
                        </div>
                    </div>
                    @if ($submission->grade?->status === 'released' && $submission->grade->feedback)
                        <div class="mt-3 rounded-lg border-l-4 border-eamn-500 bg-eamn-50 px-4 py-3 text-sm text-gray-700">
                            <p class="text-xs font-semibold uppercase tracking-wide text-eamn-700">Trainer feedback</p>
                            <p class="mt-1">{{ $submission->grade->feedback }}</p>
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-6"><x-ui.empty title="No submissions yet" /></div>
            @endforelse
        </div>
    </section>
</div>
