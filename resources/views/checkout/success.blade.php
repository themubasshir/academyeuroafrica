@extends('layouts.app', ['title' => 'Order status'])

@section('content')
    <div class="mx-auto max-w-lg text-center">
        <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
            <h1 class="text-2xl font-semibold tracking-tight">Processing your order</h1>
            <p class="mt-3 text-gray-600">
                We're confirming your payment. Your course will appear in
                <a href="{{ route('student.dashboard') }}" class="text-eamn-700 underline">My Courses</a>
                once the payment is verified.
            </p>
            @if ($order)
                <p class="mt-4 text-sm text-gray-500">Order {{ $order->reference }} — status: <strong>{{ $order->status }}</strong></p>
            @endif
        </div>
    </div>
@endsection
