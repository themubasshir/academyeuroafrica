@extends('layouts.app', ['title' => 'Checkout'])

@section('content')
    <div class="mx-auto max-w-lg py-8">
        <div class="card p-8">
            <h1 class="h1">Confirm your order</h1>

            <div class="mt-6 rounded-xl border border-gray-100 bg-gray-50 p-5">
                <p class="text-sm text-gray-500">{{ $variant->course->programme->name }}</p>
                <p class="font-semibold text-gray-900">{{ $variant->course->title }}</p>
                <span class="badge-blue mt-2">{{ $variant->name }} · {{ ucfirst($variant->delivery_type) }}</span>
                <p class="mt-4 text-3xl font-bold tracking-tight text-gray-900">{{ $variant->priceFormatted() }}</p>
            </div>

            <form method="POST" action="{{ route('checkout.store', $variant) }}" class="mt-6">
                @csrf
                <button type="submit"
                        class="w-full rounded-lg bg-[#ffc439] px-4 py-3 text-sm font-bold text-[#003087] shadow-sm transition hover:bg-[#f0b429] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-eamn-600">
                    Pay {{ $variant->priceFormatted() }} with PayPal
                </button>
            </form>
            <p class="mt-3 flex items-center justify-center gap-1.5 text-center text-xs text-gray-400">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                Redirected to PayPal to approve. No card details stored.
            </p>
        </div>
    </div>
@endsection
