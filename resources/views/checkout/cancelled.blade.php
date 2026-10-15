@extends('layouts.app', ['title' => 'Checkout cancelled'])

@section('content')
    <div class="mx-auto max-w-lg py-12 text-center">
        <div class="rounded-lg border border-paper-300 bg-white p-8">
            <p class="register">Order cancelled</p>
            <h1 class="mt-3 h1">Checkout cancelled</h1>
            <p class="mt-3 text-sm text-ink-600">No payment was taken. Your course is still reserved — restart checkout whenever you're ready.</p>
            <a href="{{ route('student.dashboard') }}" class="btn-secondary mt-6">Back to my courses</a>
        </div>
    </div>
@endsection
