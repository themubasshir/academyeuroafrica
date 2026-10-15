<div class="mx-auto max-w-4xl space-y-8">
    <h1 class="text-2xl font-semibold tracking-tight">My results</h1>

    <section>
        <h2 class="text-lg font-semibold">Quizzes</h2>
        <div class="mt-3 divide-y divide-gray-200 rounded-xl border border-gray-200 bg-white">
            @forelse ($quizAttempts as $attempt)
                <div class="flex items-center justify-between px-5 py-4">
                    <div>
                        <p class="font-medium">{{ $attempt->quiz->title }}</p>
                        <p class="text-xs text-gray-500">{{ $attempt->quiz->course->title }} · attempt {{ $attempt->attempt_no }}</p>
                    </div>
                    <div class="text-right">
                        @if ($attempt->score !== null)
                            <p class="text-xl font-semibold">{{ $attempt->score }}</p>
                        @else
                            <p class="text-sm text-gray-500">Pending</p>
                        @endif
                    </div>
                </div>
            @empty
                <p class="p-6 text-center text-sm text-gray-500">No quiz results yet.</p>
            @endforelse
        </div>
    </section>

    <section>
        <h2 class="text-lg font-semibold">Assignments</h2>
        <div class="mt-3 divide-y divide-gray-200 rounded-xl border border-gray-200 bg-white">
            @forelse ($submissions as $submission)
                <div class="px-5 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium">{{ $submission->assignment->title }}</p>
                            <p class="text-xs text-gray-500">{{ $submission->assignment->course->title }}</p>
                        </div>
                        <div class="text-right">
                            @if ($submission->grade?->status === 'released')
                                <p class="text-xl font-semibold">{{ $submission->grade->score }}/{{ $submission->assignment->max_points }}</p>
                            @else
                                <p class="text-sm text-gray-500">{{ ucfirst($submission->status) }}</p>
                            @endif
                        </div>
                    </div>
                    @if ($submission->grade?->status === 'released' && $submission->grade->feedback)
                        <p class="mt-2 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-700">{{ $submission->grade->feedback }}</p>
                    @endif
                </div>
            @empty
                <p class="p-6 text-center text-sm text-gray-500">No submissions yet.</p>
            @endforelse
        </div>
    </section>
</div>
