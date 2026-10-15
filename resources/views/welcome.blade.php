@extends('layouts.app', ['title' => 'Professional training'])

@section('content')
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-eamn-950">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--color-eamn-700)_0%,_transparent_55%)] opacity-60"></div>
        <div class="relative mx-auto max-w-6xl px-4 py-20 sm:px-6 lg:py-28">
            <p class="text-sm font-semibold uppercase tracking-widest text-gold-400">EuroAfrica Media</p>
            <h1 class="mt-3 max-w-2xl text-4xl font-bold tracking-tight text-white sm:text-5xl">
                Professional training for a connected Africa-Europe workforce.
            </h1>
            <p class="mt-5 max-w-xl text-lg text-eamn-100">
                Live, prerecorded and one-on-one programmes in media, journalism and communication — taught by working professionals.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="#programmes" class="btn-gold">Browse programmes</a>
                <a href="{{ route('bridge.start') }}" class="btn border border-white/30 text-white hover:bg-white/10">Sign in to learn</a>
            </div>
        </div>
    </section>

    {{-- Value props --}}
    <section class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
        <div class="grid gap-6 sm:grid-cols-3">
            @foreach ([
                ['Live + on-demand', 'Join scheduled live classes and revisit lessons anytime.', 'M15 10l4.5-4.5M21 3l-4.5 4.5M15 21a9 9 0 110-18'],
                ['1-on-1 mentoring', 'Book private sessions with trainers using session credits.', 'M16 7a4 4 0 11-8 0 4 4 0 018 0M12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                ['Real assessment', 'Assignments, quizzes and trainer feedback that build skill.', 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944'],
            ] as [$t, $d, $icon])
                <div class="card-pad">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-eamn-50 text-eamn-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                    </div>
                    <h3 class="mt-4 font-semibold text-gray-900">{{ $t }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ $d }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Programmes --}}
    <section id="programmes" class="border-t border-gray-200 bg-white py-16">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="flex items-end justify-between">
                <div>
                    <h2 class="text-2xl font-semibold tracking-tight text-gray-900">Programmes</h2>
                    <p class="mt-1 text-gray-500">Structured learning paths across media disciplines.</p>
                </div>
            </div>

            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($programmes as $programme)
                    <a href="{{ route('programme.show', $programme) }}"
                       class="card group flex flex-col overflow-hidden transition hover:shadow-md">
                        <div class="h-36 bg-gradient-to-br from-eamn-700 to-eamn-900 p-5">
                            <p class="text-xs font-semibold uppercase tracking-wider text-eamn-200">Programme</p>
                            <h3 class="mt-2 text-lg font-semibold text-white">{{ $programme->name }}</h3>
                        </div>
                        <div class="flex flex-1 flex-col p-5">
                            <p class="line-clamp-2 text-sm text-gray-500">{{ $programme->description }}</p>
                            <span class="mt-auto pt-4 text-sm font-semibold text-eamn-700 group-hover:underline">View courses →</span>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full"><x-ui.empty title="Programmes coming soon" message="Check back shortly." /></div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="border-t border-gray-200 bg-gray-50 py-16">
        <div class="mx-auto max-w-3xl px-4 text-center sm:px-6">
            <h2 class="text-2xl font-semibold text-gray-900">Ready to start learning?</h2>
            <p class="mt-2 text-gray-500">Sign in with your EuroAfrica Media account to enrol.</p>
            <a href="{{ route('bridge.start') }}" class="btn-primary mt-6">Sign in with EuroAfrica</a>
        </div>
    </section>
@endsection
