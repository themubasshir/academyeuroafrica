@extends('layouts.app', ['title' => 'Order status'])

@section('content')
    <div class="mx-auto max-w-lg py-12 text-center">
        <div class="card p-8">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-amber-50 text-amber-500">
                <svg class="h-7 w-7 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 3a9 9 0 109 9"/></svg>
            </div>
            <h1 class="mt-4 h1">Processing your order</h1>
            <p class="mt-3 text-sm text-gray-600">
                We're confirming your payment. Your course will appear in
                <a href="{{ route('student.dashboard') }}" class="font-medium text-eamn-700 underline">My Courses</a>
                once verified.
            </p>
            @if ($order)
                <p class="mt-4 text-xs text-gray-500">Order {{ $order->reference }} — status: <strong class="text-gray-700">{{ $order->status }}</strong></p>
            @endif
            <a href="{{ route('student.dashboard') }}" class="btn-secondary mt-6">Go to My Courses</a>
        </div>
    </div>
@endsection
