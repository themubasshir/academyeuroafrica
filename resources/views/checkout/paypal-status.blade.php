@extends('layouts.app', ['title' => 'Payment status'])

@section('content')
    <div class="mx-auto max-w-lg text-center">
        <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
            <h1 class="text-2xl font-semibold tracking-tight">Confirming payment</h1>
            <p class="mt-3 text-gray-600">{{ $message }}</p>
            @if ($order)
                <p class="mt-4 text-sm text-gray-500">Order {{ $order->reference }} — status: <strong>{{ $order->status }}</strong></p>
            @endif
            <a href="{{ route('student.dashboard') }}" class="mt-6 inline-block rounded-lg bg-eamn-700 px-5 py-2 text-sm font-semibold text-white">My courses</a>
        </div>
    </div>
@endsection
