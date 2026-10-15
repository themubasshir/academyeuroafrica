<div class="mx-auto max-w-5xl space-y-8">
    <div>
        <a href="{{ route('student.dashboard') }}" class="text-sm text-eamn-700 hover:underline">&larr; My courses</a>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ $course->title }}</h1>
        <p class="text-sm text-gray-500">{{ $enrolment->variant->name }}</p>
    </div>

    <div class="grid gap-8 lg:grid-cols-3">
        <div class="space-y-8 lg:col-span-2">
            <section>
                <h2 class="text-lg font-semibold">Curriculum</h2>
                <div class="mt-3 space-y-3">
                    @forelse ($modules as $i => $module)
                        <div class="rounded-xl border border-gray-200 bg-white">
                            <div class="border-b border-gray-100 px-5 py-3 font-medium">{{ $module->title }}</div>
                            <ul class="divide-y divide-gray-100">
                                @foreach ($module->lessons as $lesson)
                                    @php $done = isset($progress[$lesson->id]); @endphp
                                    <li>
                                        <a href="{{ route('learn.lesson', [$enrolment, $lesson]) }}"
                                           class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50">
                                            <span @class(['flex h-6 w-6 items-center justify-center rounded-full text-xs', 'bg-green-100 text-green-700' => $done, 'bg-gray-100 text-gray-400' => ! $done])>
                                                {{ $done ? '✓' : ($lesson->type === 'video' ? '▶' : '•') }}
                                            </span>
                                            <span class="flex-1 text-sm">{{ $lesson->title }}</span>
                                            @if ($lesson->video)<span class="text-xs text-gray-400">video</span>@endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @empty
                        <p class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500">No lessons yet.</p>
                    @endforelse
                </div>
            </section>

            <section>
                <h2 class="text-lg font-semibold">Assignments</h2>
                <div class="mt-3 space-y-2">
                    @forelse ($assignments as $assignment)
                        <a href="{{ route('learn.assignment', [$enrolment, $assignment]) }}"
                           class="flex items-center justify-between rounded-xl border border-gray-200 bg-white px-5 py-4 hover:border-eamn-300">
                            <span class="font-medium">{{ $assignment->title }}</span>
                            <span class="text-sm text-gray-500">{{ $assignment->due_at?->format('M j, Y') ?? 'No deadline' }}</span>
                        </a>
                    @empty
                        <p class="rounded-xl border border-dashed border-gray-300 bg-white p-6 text-center text-sm text-gray-500">No assignments.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section>
                <h2 class="text-lg font-semibold">Upcoming live</h2>
                <div class="mt-3 space-y-2">
                    @forelse ($sessions as $session)
                        <div class="rounded-xl border border-gray-200 bg-white p-4">
                            <p class="font-medium text-sm">{{ $session->title }}</p>
                            <p class="text-xs text-gray-500">{{ $session->starts_at->setTimezone(auth()->user()->timezone ?? 'UTC')->format('M j, g:i A T') }}</p>
                            @if ($session->meeting_url)
                                <a href="{{ $session->meeting_url }}" target="_blank" rel="noopener"
                                   class="mt-2 inline-block text-sm font-medium text-eamn-700 hover:underline">Join {{ ucfirst($session->provider) }}</a>
                            @endif
                        </div>
                    @empty
                        <p class="rounded-xl border border-dashed border-gray-300 bg-white p-5 text-center text-sm text-gray-500">None scheduled.</p>
                    @endforelse
                </div>
            </section>

            <section>
                <h2 class="text-lg font-semibold">Quizzes</h2>
                <div class="mt-3 space-y-2">
                    @forelse ($quizzes as $quiz)
                        <a href="{{ route('learn.quiz', [$enrolment, $quiz]) }}"
                           class="block rounded-xl border border-gray-200 bg-white p-4 text-sm font-medium hover:border-eamn-300">
                            {{ $quiz->title }}
                        </a>
                    @empty
                        <p class="rounded-xl border border-dashed border-gray-300 bg-white p-5 text-center text-sm text-gray-500">No quizzes.</p>
                    @endforelse
                </div>
            </section>

            @if ($materials->isNotEmpty())
                <section>
                    <h2 class="text-lg font-semibold">Materials</h2>
                    <ul class="mt-3 space-y-1 rounded-xl border border-gray-200 bg-white p-3">
                        @foreach ($materials as $material)
                            <li>
                                <a href="{{ route('files.material', $material) }}"
                                   class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-eamn-700 hover:bg-eamn-50">
                                    <span>⭳</span> {{ $material->original_name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>
</div>
