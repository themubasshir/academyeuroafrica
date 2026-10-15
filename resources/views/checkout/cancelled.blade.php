@extends('layouts.app', ['title' => 'Checkout cancelled'])

@section('content')
    <div class="mx-auto max-w-lg text-center">
        <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
            <h1 class="text-2xl font-semibold tracking-tight">Checkout cancelled</h1>
            <p class="mt-3 text-gray-600">No payment was taken. You can try again whenever you're ready.</p>
        </div>
    </div>
@endsection
