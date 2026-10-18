@extends('layouts.app', ['title' => 'Sign in'])

@section('content')
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-navy-900 px-4 py-10">
        {{-- faint register rules in the field --}}
        <div class="pointer-events-none absolute inset-0 opacity-[0.05]" aria-hidden="true"
             style="background-image:repeating-linear-gradient(to bottom, transparent 0 39px, #fff 39px 40px);"></div>

        <div class="relative w-full max-w-md">
            {{-- Register panel --}}
            <div class="rounded-lg border border-navy-700/60 bg-paper-50 p-7 shadow-[0_20px_50px_rgba(4,16,32,0.5)] sm:p-9">
                <div class="flex flex-col items-start">
                    <x-ui.brand size="lg" />
                    <p class="register mt-5">Academy sign-in</p>
                    <div class="mt-2 h-px w-full bg-paper-300"></div>
                </div>

                <h1 class="mt-6 h1">Welcome back to the register.</h1>
                <p class="mt-2 text-sm leading-relaxed text-ink-600">
                    EuroAfrica Media members sign in through the main site for one-click access to their courses.
                </p>

                <a href="{{ route('bridge.start') }}" class="btn-primary mt-6 w-full py-3 text-base">
                    Sign in with EuroAfrica
                </a>

                <div class="mt-7 flex items-center gap-3" aria-hidden="true">
                    <span class="h-px flex-1 bg-paper-300"></span>
                    <span class="folio">staff &amp; local accounts</span>
                    <span class="h-px flex-1 bg-paper-300"></span>
                </div>

                @if ($errors->any())
                    <div class="mt-5 rounded-md border border-red-300 bg-red-50 p-3 text-sm text-red-800" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="mt-5 space-y-4">
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
                    <div class="mt-2 flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                        <a href="{{ route('password.request') }}" class="text-sm text-reg-700 underline underline-offset-2">Forgot Password?</a>
                        <label for="remember" class="flex items-center gap-2 text-sm text-ink-600">
                            <input id="remember" name="remember" type="checkbox" class="h-4 w-4 rounded border-paper-300 text-reg-600 focus:ring-reg-600/40">
                            Remember me
                        </label>
                        <button type="submit" class="btn-secondary btn-sm">Sign in</button>
                    </div>
                </form>
                <div class="mt-4 text-right text-sm"><a href="{{ route('register') }}" class="text-reg-700 underline underline-offset-2">Create student account</a></div>
            </div>

            {{-- Registry footer line --}}
            <p class="mt-6 text-center text-xs font-medium tracking-wide text-navy-100/85">
                A private learning register · EuroAfrica Media Network
            </p>
        </div>
    </div>
@endsection
