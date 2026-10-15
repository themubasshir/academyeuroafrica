@extends('layouts.app', ['title' => 'Checkout cancelled'])

@section('content')
    <div class="mx-auto max-w-lg py-12 text-center">
        <div class="card p-8">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <h1 class="mt-4 h1">Checkout cancelled</h1>
            <p class="mt-3 text-sm text-gray-600">No payment was taken. You can try again whenever you're ready.</p>
            <a href="{{ route('home') }}#programmes" class="btn-secondary mt-6">Back to programmes</a>
        </div>
    </div>
@endsection
