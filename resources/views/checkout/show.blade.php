@extends('layouts.app', ['title' => 'Checkout'])

@section('content')
    <div class="mx-auto max-w-lg py-8">
        <div class="rounded-lg border border-paper-300 bg-white p-7 shadow-[0_1px_2px_rgba(19,26,36,0.05)] sm:p-9">
            <p class="register">Order confirmation</p>
            <h1 class="mt-2 h1">Confirm your order</h1>
            <div class="mt-4 h-px w-full bg-paper-300"></div>

            {{-- Order as a ledger entry --}}
            <dl class="mt-6 space-y-3">
                <div class="flex justify-between gap-4 border-b border-paper-200 pb-3">
                    <dt class="text-sm text-ink-500">Programme</dt>
                    <dd class="text-right text-sm font-medium text-ink-900">{{ $variant->course->programme->name }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-paper-200 pb-3">
                    <dt class="text-sm text-ink-500">Course</dt>
                    <dd class="text-right text-sm font-semibold text-ink-900">{{ $variant->course->title }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-paper-200 pb-3">
                    <dt class="text-sm text-ink-500">Enrolment</dt>
                    <dd><span class="badge-blue">{{ $variant->name }} · {{ ucfirst($variant->delivery_type) }}</span></dd>
                </div>
                <div class="flex items-baseline justify-between gap-4 pt-1">
                    <dt class="text-sm font-semibold text-ink-900">Total</dt>
                    <dd class="font-display text-3xl font-semibold text-ink-950">{{ $variant->priceFormatted() }}</dd>
                </div>
            </dl>

            <form method="POST" action="{{ route('checkout.store', $variant) }}" class="mt-7">
                @csrf
                <button type="submit"
                        class="w-full rounded-md bg-[#ffc439] px-4 py-3 text-sm font-bold text-[#003087] shadow-sm transition hover:bg-[#f0b429] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-reg-600">
                    Pay {{ $variant->priceFormatted() }} with PayPal
                </button>
            </form>
            <p class="mt-3 flex items-center justify-center gap-1.5 text-center text-xs text-ink-600">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                Redirected to PayPal to approve. No card details stored.
            </p>
        </div>
    </div>
@endsection
