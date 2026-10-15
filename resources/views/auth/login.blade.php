@extends('layouts.app', ['title' => 'Log in'])

@section('content')
    <div class="mx-auto max-w-md">
        <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
            <h1 class="text-2xl font-semibold tracking-tight">Sign in</h1>
            <p class="mt-1 text-sm text-gray-500">
                EuroAfrica Media members: use the academy button on the main site to sign in automatically.
            </p>

            @if ($errors->any())
                <div class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input id="email" name="email" type="email" required autocomplete="email" autofocus
                           value="{{ old('email') }}"
                           class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-eamn-500 focus:outline-none focus:ring-1 focus:ring-eamn-500">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                           class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-eamn-500 focus:outline-none focus:ring-1 focus:ring-eamn-500">
                </div>
                <div class="flex items-center">
                    <input id="remember" name="remember" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-eamn-600 focus:ring-eamn-500">
                    <label for="remember" class="ml-2 text-sm text-gray-600">Remember me</label>
                </div>
                <button type="submit"
                        class="w-full rounded-lg bg-eamn-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-eamn-800 focus:outline-none focus:ring-2 focus:ring-eamn-500 focus:ring-offset-2">
                    Sign in
                </button>
            </form>
        </div>
    </div>
@endsection
