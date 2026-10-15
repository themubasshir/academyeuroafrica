<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Welcome back, {{ auth()->user()->name }}</h1>
        <p class="mt-1 text-sm text-gray-500">Pick up where you left off.</p>
    </div>

    <section aria-labelledby="courses-heading">
        <h2 id="courses-heading" class="text-lg font-medium">My courses</h2>
        <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($enrolments as $enrolment)
                @php $course = $enrolment->variant->course; @endphp
                <a href="{{ route('learn.course', $enrolment) }}"
                   class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:border-eamn-300">
                    <p class="text-xs text-eamn-700">{{ $course->programme->name }}</p>
                    <h3 class="mt-1 font-semibold group-hover:text-eamn-800">{{ $course->title }}</h3>
                    <p class="mt-1 text-xs text-gray-500">{{ $enrolment->variant->name }}</p>
                </a>
            @empty
                <p class="col-span-full rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
                    No courses yet. Purchased courses appear here.
                </p>
            @endforelse
        </div>
    </section>

    <div class="grid gap-6 sm:grid-cols-2">
        <section aria-labelledby="live-heading">
            <h2 id="live-heading" class="text-lg font-medium">Upcoming live classes</h2>
            <div class="mt-3 space-y-2">
                @forelse ($upcoming as $s)
                    <div class="rounded-xl border border-gray-200 bg-white p-4">
                        <p class="text-sm font-medium">{{ $s->title }}</p>
                        <p class="text-xs text-gray-500">{{ $s->starts_at->setTimezone(auth()->user()->timezone ?? 'UTC')->format('M j, g:i A T') }}</p>
                    </div>
                @empty
                    <p class="rounded-xl border border-dashed border-gray-300 bg-white p-6 text-center text-sm text-gray-500">None scheduled.</p>
                @endforelse
            </div>
        </section>
        <section aria-labelledby="due-heading">
            <h2 id="due-heading" class="text-lg font-medium">Assignments due</h2>
            <div class="mt-3 space-y-2">
                @forelse ($due as $a)
                    <a href="{{ route('learn.assignment', [$enrolments->firstWhere('variant.course_id', $a->course_id), $a]) }}"
                       class="block rounded-xl border border-gray-200 bg-white p-4 text-sm font-medium hover:border-eamn-300">
                        {{ $a->title }}
                        <span class="block text-xs font-normal text-gray-500">Due {{ $a->due_at->format('M j, g:i A') }}</span>
                    </a>
                @empty
                    <p class="rounded-xl border border-dashed border-gray-300 bg-white p-6 text-center text-sm text-gray-500">Nothing due.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
