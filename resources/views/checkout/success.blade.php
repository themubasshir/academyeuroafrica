@extends('layouts.app', ['title' => 'Order status'])

@section('content')
    <div class="mx-auto max-w-lg py-12 text-center">
        <div class="rounded-lg border border-paper-300 bg-white p-8">
            <span class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-seal-100 bg-seal-50 text-seal-600">
                <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 3a9 9 0 109 9"/></svg>
            </span>
            <h1 class="mt-4 h1">Processing your order</h1>
            <p class="mt-3 text-sm text-ink-600">
                We're confirming your payment. Your course will be registered in
                <a href="{{ route('student.dashboard') }}" class="link">My Courses</a>
                once verified.
            </p>
            @if ($order)
                <p class="folio mt-5">Order {{ $order->reference }} · status <strong class="text-ink-700">{{ $order->status }}</strong></p>
            @endif
            <a href="{{ route('student.dashboard') }}" class="btn-secondary mt-6">Go to My Courses</a>
        </div>
    </div>
@endsection
