<div class="space-y-6">
    <h1 class="h1">Orders</h1>
    <div class="card overflow-x-auto">
        <table class="table-base">
            <thead class="border-b border-gray-100 bg-gray-50">
                <tr><th>Reference</th><th>Customer</th><th>Items</th><th class="text-right">Total</th><th class="text-right">Status</th></tr>
            </thead>
            <tbody>
                @forelse ($orders as $o)
                    <tr>
                        <td class="font-mono text-xs">{{ $o->reference }}</td>
                        <td>{{ $o->user->email }}</td>
                        <td>{{ $o->items->count() }}</td>
                        <td class="text-right font-semibold">{{ number_format($o->total_cents/100,2) }} {{ $o->currency }}</td>
                        <td class="text-right">
                            <span @class(['badge-green' => $o->isPaid(), 'badge-amber' => ! $o->isPaid()])>{{ ucfirst($o->status) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-gray-500">No orders.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
