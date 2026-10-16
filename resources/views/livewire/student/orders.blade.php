<div class="mx-auto max-w-5xl space-y-8">
    <x-ui.page-head register="Student register" title="Order history" :folio="'Ref EAMN-'.auth()->id()" />
    <div class="card overflow-x-auto">
        <table class="table-base"><caption class="sr-only">Your Academy orders</caption><thead class="bg-paper-50"><tr><th>Reference</th><th>Purchased</th><th>Date</th><th>Payment</th><th class="text-right">Amount</th></tr></thead><tbody>
        @forelse ($orders as $order)
            <tr><td class="folio">{{ $order->reference }}</td><td>{{ $order->items->pluck('name')->join(', ') }}</td><td>{{ $order->created_at->format('j M Y') }}</td><td><span class="badge-gray">{{ ucfirst($order->status) }}</span>@if ($order->refundedCents() > 0) <span class="badge-amber ml-1">Refunded</span>@endif</td><td class="text-right font-semibold">{{ number_format($order->total_cents / 100, 2) }} {{ $order->currency }}</td></tr>
        @empty
            <tr><td colspan="5" class="py-10 text-center text-ink-500">No orders yet. Your purchases will appear here.</td></tr>
        @endforelse
        </tbody></table>
    </div>
    {{ $orders->links() }}
</div>
