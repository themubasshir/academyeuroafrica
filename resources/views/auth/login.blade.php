@extends('layouts.app', ['title' => 'Log in'])

@section('content')
    <div class="mx-auto max-w-md py-12">
        <div class="card p-8">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-eamn-700 text-lg font-extrabold text-white">E</div>
            <h1 class="mt-4 text-2xl font-semibold tracking-tight">Sign in</h1>
            <p class="mt-1 text-sm text-gray-500">
                EuroAfrica Media members: sign in via the main site for one-click access.
            </p>

            @if ($errors->any())
                <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5">
                @csrf
                <div>
                    <label for="email" class="label">Email</label>
                    <input id="email" name="email" type="email" required autocomplete="email" autofocus
                           value="{{ old('email') }}" class="input">
                </div>
                <div>
                    <label for="password" class="label">Password</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="input">
                </div>
                <div class="flex items-center">
                    <input id="remember" name="remember" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-eamn-600 focus:ring-eamn-500">
                    <label for="remember" class="ml-2 text-sm text-gray-600">Remember me</label>
                </div>
                <button type="submit" class="btn-primary w-full">Sign in</button>
            </form>

            <div class="mt-6 border-t border-gray-100 pt-5 text-center">
                <a href="{{ route('bridge.start') }}" class="btn-secondary w-full">Sign in with EuroAfrica</a>
            </div>
        </div>
    </div>
@endsection
