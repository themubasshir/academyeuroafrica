<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">{{ $programme->name }}</h1>
        @if ($programme->description)
            <p class="mt-1 text-gray-600">{{ $programme->description }}</p>
        @endif
    </div>

    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($courses as $course)
            <a href="{{ route('course.show', $course) }}"
               class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-eamn-300 hover:shadow">
                <h2 class="font-semibold text-gray-900 group-hover:text-eamn-800">{{ $course->title }}</h2>
                <p class="mt-1 line-clamp-2 text-sm text-gray-500">{{ $course->description }}</p>
                <div class="mt-4 text-sm font-medium text-eamn-700">
                    @foreach ($course->variants as $variant)
                        <div class="flex justify-between">
                            <span>{{ $variant->name }}</span>
                            <span>{{ $variant->priceFormatted() }}</span>
                        </div>
                    @endforeach
                </div>
            </a>
        @empty
            <p class="col-span-full rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
                No courses available yet.
            </p>
        @endforelse
    </div>
</div>
