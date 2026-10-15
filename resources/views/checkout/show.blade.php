@extends('layouts.app', ['title' => 'Checkout'])

@section('content')
    <div class="mx-auto max-w-lg">
        <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
            <h1 class="text-2xl font-semibold tracking-tight">Confirm your order</h1>

            <div class="mt-6 rounded-lg bg-gray-50 p-4">
                <p class="font-medium">{{ $variant->course->title }}</p>
                <p class="text-sm text-gray-500">{{ $variant->name }} · {{ ucfirst($variant->delivery_type) }}</p>
                <p class="mt-3 text-2xl font-semibold">{{ $variant->priceFormatted() }}</p>
            </div>

            <form method="POST" action="{{ route('checkout.store', $variant) }}" class="mt-6">
                @csrf
                <button type="submit"
                        class="w-full rounded-lg bg-[#ffc439] px-4 py-2.5 text-sm font-semibold text-[#003087] hover:bg-[#f0b429]">
                    Pay {{ $variant->priceFormatted() }} with PayPal
                </button>
            </form>
            <p class="mt-3 text-center text-xs text-gray-400">You'll be redirected to PayPal to approve the payment. No card details are stored.</p>
        </div>
    </div>
@endsection
