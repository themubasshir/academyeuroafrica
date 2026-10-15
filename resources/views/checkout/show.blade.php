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
                        class="w-full rounded-lg bg-eamn-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-eamn-800">
                    Pay {{ $variant->priceFormatted() }}
                </button>
            </form>
            <p class="mt-3 text-center text-xs text-gray-400">Secure checkout — you'll be redirected to the payment provider.</p>
        </div>
    </div>
@endsection
