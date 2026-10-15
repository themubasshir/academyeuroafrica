<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }} — EAMN Academy</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full bg-gray-50 text-gray-900 antialiased">
    <div class="flex min-h-screen">
        @auth
            <x-layouts.sidebar />
        @endauth

        <div class="flex-1 flex flex-col min-w-0">
            <header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-gray-200 bg-white/80 px-4 backdrop-blur sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="text-lg font-semibold text-eamn-800">EAMN Academy</a>
                <div class="ml-auto flex items-center gap-3">
                    @auth
                        <span class="text-sm text-gray-600">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-sm font-medium text-gray-600 hover:text-gray-900">Log out</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-eamn-700 hover:text-eamn-900">Log in</a>
                    @endauth
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                {{ $slot ?? '' }}
                @yield('content')
            </main>
        </div>
    </div>
    @livewireScriptConfig
</body>
</html>
