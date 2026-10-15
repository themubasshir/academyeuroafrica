<div class="mx-auto max-w-4xl space-y-8">
    <x-ui.page-head :register="$course->programme->name" :title="$course->title" />
    @if ($course->description)
        <p class="max-w-2xl leading-relaxed text-ink-600">{{ $course->description }}</p>
    @endif

    @if ($course->learning_outcomes)
        <section>
            <div class="register-rule"><h2 class="register shrink-0">What you'll learn</h2></div>
            <ul class="mt-3 list-inside list-disc space-y-1 text-ink-700">
                @foreach ($course->learning_outcomes as $outcome)
                    <li>{{ $outcome }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($modules->isNotEmpty())
        <section>
            <div class="register-rule"><h2 class="register shrink-0">Curriculum</h2></div>
            <div class="ledger mt-3">
                @foreach ($modules as $module)
                    <div class="px-5 py-4">
                        <h3 class="text-sm font-semibold text-ink-900">{{ $module->title }}</h3>
                        <ul class="mt-1.5 space-y-0.5 text-sm text-ink-500">
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
        <div class="register-rule"><h2 class="register shrink-0">Enrolment options</h2></div>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            @foreach ($variants as $variant)
                <div class="card-pad">
                    <p class="font-semibold text-ink-900">{{ $variant->name }}</p>
                    <p class="text-sm text-ink-500">{{ ucfirst($variant->delivery_type) }}</p>
                    <p class="mt-3 font-display text-2xl font-semibold text-ink-950">{{ $variant->priceFormatted() }}</p>
                    <a href="{{ route('checkout.show', $variant) }}" class="btn-primary mt-4 w-full">Purchase course</a>
                </div>
            @endforeach
        </div>
    </section>
</div>
