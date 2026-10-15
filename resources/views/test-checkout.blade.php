@extends('layouts.app', ['title' => 'Test checkout'])

@section('content')
    <div class="mx-auto max-w-lg">
        <div class="rounded-2xl border-2 border-dashed border-amber-300 bg-amber-50 p-8">
            <p class="text-xs font-bold uppercase tracking-wide text-amber-700">Local test adapter — not for production</p>
            <h1 class="mt-2 text-xl font-semibold">Simulated payment</h1>
            <p class="mt-2 text-sm text-gray-700">Order {{ $order->reference }} — {{ number_format($order->total_cents/100, 2) }} {{ $order->currency }}</p>
            <form method="POST" action="{{ route('test-checkout.pay', $order->reference) }}" class="mt-6">
                @csrf
                <button class="w-full rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                    Simulate successful payment
                </button>
            </form>
        </div>
    </div>
@endsection
