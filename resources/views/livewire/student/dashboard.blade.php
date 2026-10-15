<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Welcome back, {{ auth()->user()->name }}</h1>
        <p class="mt-1 text-sm text-gray-500">Pick up where you left off.</p>
    </div>

    <section aria-labelledby="courses-heading">
        <h2 id="courses-heading" class="text-lg font-medium">My courses</h2>
        <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($enrolments as $enrolment)
                @php $course = $enrolment->variant->course; @endphp
                <a href="{{ route('course.show', $course) }}"
                   class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:border-eamn-300">
                    <p class="text-xs text-eamn-700">{{ $course->programme->name }}</p>
                    <h3 class="mt-1 font-semibold group-hover:text-eamn-800">{{ $course->title }}</h3>
                    <p class="mt-1 text-xs text-gray-500">{{ $enrolment->variant->name }} · {{ $enrolment->status }}</p>
                </a>
            @empty
                <p class="col-span-full rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
                    No courses yet. Purchased courses appear here.
                </p>
            @endforelse
        </div>
    </section>
</div>
