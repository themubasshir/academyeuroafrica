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
<body class="min-h-full bg-gray-50 text-gray-900 antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[60] focus:rounded-lg focus:bg-eamn-700 focus:px-4 focus:py-2 focus:text-white">
        Skip to content
    </a>
    <x-ui.toasts />

    @auth
        <div x-data="{ nav: false }" class="flex min-h-screen">
            {{-- Mobile top bar + drawer --}}
            <div class="fixed inset-x-0 top-0 z-40 flex h-16 items-center gap-3 border-b border-gray-200 bg-white/90 px-4 backdrop-blur lg:hidden">
                <button @click="nav = true" class="rounded-lg p-2 text-gray-600 hover:bg-gray-100" aria-label="Open navigation" :aria-expanded="nav">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <a href="{{ route('home') }}" class="text-base font-semibold text-eamn-800">EAMN Academy</a>
                <span class="ml-auto text-sm text-gray-600">{{ auth()->user()->name }}</span>
            </div>
            <div x-show="nav" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Navigation">
                <div x-show="nav" @click="nav = false" class="absolute inset-0 bg-gray-900/50" x-transition.opacity></div>
                <div x-show="nav" class="absolute inset-y-0 left-0 w-72 bg-white shadow-xl" x-transition:enter="transition" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0">
                    <div class="flex h-16 items-center justify-between border-b border-gray-100 px-4">
                        <span class="font-semibold text-eamn-800">EAMN Academy</span>
                        <button @click="nav = false" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100" aria-label="Close navigation">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <x-layouts.sidebar :mobile="true" />
                </div>
            </div>

            {{-- Desktop sidebar --}}
            <x-layouts.sidebar />

            <div class="flex min-w-0 flex-1 flex-col pt-16 lg:pt-0">
                <header class="sticky top-0 z-30 hidden h-16 items-center gap-4 border-b border-gray-200 bg-white/80 px-6 backdrop-blur lg:flex lg:px-8">
                    <a href="{{ route('home') }}" class="text-lg font-semibold text-eamn-800">EAMN Academy</a>
                    <div class="ml-auto flex items-center gap-4">
                        <x-ui.avatar :name="auth()->user()->name" />
                        <span class="text-sm font-medium text-gray-700">{{ auth()->user()->name }}</span>
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
        {{-- Public / guest chrome --}}
        <div class="flex min-h-screen flex-col">
            <header class="sticky top-0 z-30 border-b border-gray-200 bg-white/90 backdrop-blur">
                <div class="mx-auto flex h-16 max-w-6xl items-center gap-4 px-4 sm:px-6">
                    <a href="{{ route('home') }}" class="flex items-center gap-2 text-lg font-bold text-eamn-800">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-eamn-700 text-sm font-extrabold text-white">E</span>
                        EAMN Academy
                    </a>
                    <nav class="ml-auto hidden items-center gap-1 sm:flex" aria-label="Public">
                        <a href="{{ route('home') }}#programmes" class="btn-ghost btn-sm">Programmes</a>
                        <a href="{{ route('login') }}" class="btn-ghost btn-sm">Log in</a>
                        <a href="{{ route('bridge.start') }}" class="btn-primary btn-sm">Sign in with EuroAfrica</a>
                    </nav>
                    <a href="{{ route('login') }}" class="btn-primary btn-sm ml-auto sm:hidden">Log in</a>
                </div>
            </header>
            <main id="main-content" class="flex-1">
                {{ $slot ?? '' }}
                @yield('content')
            </main>
            <footer class="border-t border-gray-200 bg-white py-8">
                <div class="mx-auto max-w-6xl px-4 text-sm text-gray-500 sm:px-6">
                    <p class="font-semibold text-gray-700">EAMN Academy</p>
                    <p class="mt-1">EuroAfrica Media professional training · <a href="https://euroafricamedia.eu" class="text-eamn-700 hover:underline">euroafricamedia.eu</a></p>
                </div>
            </footer>
        </div>
    @endauth

    @livewireScriptConfig
</body>
</html>
