<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="EAMN Academy — EuroAfrica Media professional courses and training.">
    <title>{{ $title ?? config('app.name') }} — EAMN Academy</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full bg-paper-50 text-ink-900 antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[60] focus:rounded-md focus:bg-reg-700 focus:px-4 focus:py-2 focus:text-white">
        Skip to content
    </a>
    <x-ui.toasts />

    @auth
        <div x-data="{ nav: false }" class="flex min-h-screen">
            {{-- Mobile top bar + drawer --}}
            <div class="fixed inset-x-0 top-0 z-40 flex h-14 items-center gap-3 border-b border-navy-800 bg-navy-900 px-4 lg:hidden">
                <button @click="nav = true" class="rounded-md p-2 text-navy-100 hover:bg-navy-800" aria-label="Open navigation" :aria-expanded="nav">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <a href="{{ route('home') }}" class="text-navy-50"><x-ui.brand dark size="sm" /></a>
                <span class="ml-auto truncate text-xs font-medium text-navy-100">{{ auth()->user()->name }}</span>
            </div>
            <div x-show="nav" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Navigation">
                <div x-show="nav" @click="nav = false" class="absolute inset-0 bg-ink-950/60" x-transition.opacity></div>
                <div x-show="nav" class="absolute inset-y-0 left-0 w-72 bg-navy-900 shadow-xl" x-transition:enter="transition" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0">
                    <div class="flex h-14 items-center justify-between border-b border-navy-800 px-4">
                        <x-ui.brand dark size="sm" />
                        <button @click="nav = false" class="rounded-md p-2 text-navy-100 hover:bg-navy-800" aria-label="Close navigation">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <x-layouts.sidebar :mobile="true" />
                </div>
            </div>

            {{-- Desktop sidebar --}}
            <x-layouts.sidebar />

            <div class="flex min-w-0 flex-1 flex-col pt-14 lg:pt-0">
                <header class="sticky top-0 z-30 hidden h-14 items-center gap-4 border-b border-paper-300 bg-paper-50/85 px-6 backdrop-blur lg:flex lg:px-8">
                    <a href="{{ route('home') }}" class="text-ink-950"><x-ui.brand size="sm" /></a>
                    <div class="ml-auto flex items-center gap-4">
                        <x-ui.avatar :name="auth()->user()->name" />
                        <span class="text-sm font-medium text-ink-700">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn-ghost btn-sm">Log out</button>
                        </form>
                    </div>
                </header>

                <main id="main-content" class="flex-1 p-4 sm:p-6 lg:p-8">
                    {{ $slot ?? '' }}
                    @yield('content')
                </main>
            </div>
        </div>
    @else
        {{-- Guest chrome: minimal, brand-led. `/` routes to login. --}}
        <main id="main-content" class="flex-1">
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    @endauth

    @livewireScriptConfig
</body>
</html>
