<div class="mx-auto max-w-4xl space-y-8">
    <div>
        <p class="text-sm text-eamn-700">{{ $course->programme->name }}</p>
        <h1 class="mt-1 text-3xl font-semibold tracking-tight">{{ $course->title }}</h1>
        @if ($course->description)
            <p class="mt-3 text-gray-600">{{ $course->description }}</p>
        @endif
    </div>

    @if ($course->learning_outcomes)
        <section>
            <h2 class="text-lg font-semibold">What you'll learn</h2>
            <ul class="mt-2 list-inside list-disc space-y-1 text-gray-700">
                @foreach ($course->learning_outcomes as $outcome)
                    <li>{{ $outcome }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($modules->isNotEmpty())
        <section>
            <h2 class="text-lg font-semibold">Curriculum</h2>
            <div class="mt-3 divide-y divide-gray-200 rounded-xl border border-gray-200 bg-white">
                @foreach ($modules as $module)
                    <div class="p-4">
                        <h3 class="font-medium">{{ $module->title }}</h3>
                        <ul class="mt-1 space-y-0.5 text-sm text-gray-500">
                            @foreach ($module->lessons as $lesson)
                                <li>{{ $lesson->title }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section>
        <h2 class="text-lg font-semibold">Enrolment options</h2>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            @foreach ($variants as $variant)
                <div class="rounded-xl border border-gray-200 bg-white p-5">
                    <p class="font-medium">{{ $variant->name }}</p>
                    <p class="text-sm text-gray-500">{{ ucfirst($variant->delivery_type) }}</p>
                    <p class="mt-3 text-2xl font-semibold">{{ $variant->priceFormatted() }}</p>
                    <a href="{{ route('checkout.show', $variant) }}"
                       class="mt-4 block rounded-lg bg-eamn-700 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-eamn-800">
                        Purchase course
                    </a>
                </div>
            @endforeach
        </div>
    </section>
</div>
