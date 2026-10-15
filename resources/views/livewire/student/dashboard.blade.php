<div class="space-y-8">
    <x-ui.page-head register="Student register" :title="'Welcome back, '.auth()->user()->name" :folio="'Ref EAMN-'.auth()->id()" />

    {{-- My courses --}}
    <section aria-labelledby="courses-heading">
        <div class="register-rule">
            <h2 id="courses-heading" class="register shrink-0">Enrolled courses</h2>
        </div>

        <div class="mt-4">
            @forelse ($enrolments as $enrolment)
                @php $course = $enrolment->variant->course; @endphp
                <a href="{{ route('learn.course', $enrolment) }}"
                   class="group mb-3 flex items-center gap-4 rounded-lg border border-paper-300 bg-white px-5 py-4 shadow-[0_1px_2px_rgba(19,26,36,0.05)] transition hover:border-reg-500/50 hover:shadow-sm">
                    <span class="folio w-24 shrink-0">EAMN-{{ str_pad($enrolment->id, 4, '0', STR_PAD_LEFT) }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-display text-base font-semibold text-ink-950 group-hover:text-reg-700">{{ $course->title }}</span>
                        <span class="mt-0.5 block truncate text-xs text-ink-500">{{ $course->programme->name }} · {{ $enrolment->variant->name }}</span>
                    </span>
                    <span class="shrink-0 text-sm font-semibold text-reg-700">Continue →</span>
                </a>
            @empty
                <x-ui.empty title="No courses yet" message="Purchased courses are registered here. Enrolment happens through the EuroAfrica site." />
            @endforelse
        </div>
    </section>

    <div class="grid gap-8 lg:grid-cols-2">
        <section aria-labelledby="live-heading">
            <div class="register-rule"><h2 id="live-heading" class="register shrink-0">Upcoming live classes</h2></div>
            <div class="ledger mt-4">
                @forelse ($upcoming as $s)
                    <div class="ledger-row">
                        <div class="flex items-center gap-4">
                            <span class="flex w-11 shrink-0 flex-col items-center border-r border-paper-200 pr-4 text-center">
                                <span class="font-display text-xl font-semibold leading-none text-ink-950">{{ $s->starts_at->format('d') }}</span>
                                <span class="folio mt-0.5 text-ink-600">{{ $s->starts_at->format('M') }}</span>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-ink-900">{{ $s->title }}</p>
                                <p class="text-xs text-ink-500">{{ $s->starts_at->setTimezone(auth()->user()->timezone ?? 'UTC')->format('g:i A T') }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <x-ui.empty title="None scheduled" message="Upcoming live classes appear here." />
                @endforelse
            </div>
        </section>

        <section aria-labelledby="due-heading">
            <div class="register-rule"><h2 id="due-heading" class="register shrink-0">Assignments due</h2></div>
            <div class="ledger mt-4">
                @forelse ($due as $a)
                    <a href="{{ route('learn.assignment', [$enrolments->firstWhere('variant.course_id', $a->course_id), $a]) }}"
                       class="ledger-row">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-ink-900">{{ $a->title }}</p>
                            <p class="text-xs font-medium text-amber-700">Due {{ $a->due_at->format('M j, g:i A') }}</p>
                        </div>
                        <span class="shrink-0 text-sm font-semibold text-reg-700">Open →</span>
                    </a>
                @empty
                    <x-ui.empty title="Nothing due" message="Pending assignments appear here." />
                @endforelse
            </div>
        </section>
    </div>
</div>
