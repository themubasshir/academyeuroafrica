<div class="space-y-6">
    <h1 class="text-2xl font-semibold tracking-tight">Orders</h1>
    <div class="divide-y divide-gray-200 rounded-xl border border-gray-200 bg-white">
        @forelse ($orders as $o)
            <div class="flex items-center justify-between px-5 py-3">
                <div>
                    <p class="font-medium text-sm">{{ $o->reference }}</p>
                    <p class="text-xs text-gray-500">{{ $o->user->email }} · {{ $o->items->count() }} item(s)</p>
                </div>
                <div class="text-right">
                    <p class="font-semibold">{{ number_format($o->total_cents/100,2) }} {{ $o->currency }}</p>
                    <p class="text-xs @class(['text-green-600' => $o->isPaid(), 'text-amber-600' => ! $o->isPaid()])>{{ ucfirst($o->status) }}</p>
                </div>
            </div>
        @empty
            <p class="p-8 text-center text-sm text-gray-500">No orders.</p>
        @endforelse
    </div>
</div>
