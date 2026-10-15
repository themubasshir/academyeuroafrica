@extends('layouts.app', ['title' => 'Payment status'])

@section('content')
    <div class="mx-auto max-w-lg py-12 text-center">
        <div class="card p-8">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-eamn-50 text-eamn-600">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3"/></svg>
            </div>
            <h1 class="mt-4 h1">Confirming payment</h1>
            <p class="mt-3 text-sm text-gray-600">{{ $message }}</p>
            @if ($order)
                <p class="mt-4 text-xs text-gray-500">Order {{ $order->reference }} — status: <strong class="text-gray-700">{{ $order->status }}</strong></p>
            @endif
            <a href="{{ route('student.dashboard') }}" class="btn-primary mt-6">My courses</a>
        </div>
    </div>
@endsection
