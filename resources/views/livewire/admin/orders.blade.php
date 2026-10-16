<div class="space-y-6">
    <x-ui.page-head register="Administration" title="Orders" />

    @if ($refundOrderId)
        <form wire:submit="refund" class="card-pad space-y-4">
            <h2 class="h3">Process refund</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block text-sm font-medium">Amount
                    <input wire:model="refundAmount" type="number" min="0.01" step="0.01" class="input mt-1" required>
                </label>
                <label class="block text-sm font-medium">Reason
                    <input wire:model="refundReason" type="text" maxlength="255" class="input mt-1">
                </label>
            </div>
            <div class="flex gap-2">
                <button class="btn-primary" type="submit">Confirm refund</button>
                <button class="btn-ghost" type="button" wire:click="$set('refundOrderId', null)">Cancel</button>
            </div>
        </form>
    @endif
    <div class="card overflow-x-auto">
        <table class="table-base">
            <thead class="bg-paper-50">
                <tr><th>Reference</th><th>Customer</th><th>Items</th><th class="text-right">Total</th><th class="text-right">Status</th><th class="text-right">Action</th></tr>
            </thead>
            <tbody>
                @forelse ($orders as $o)
                    <tr>
                        <td class="folio">{{ $o->reference }}</td>
                        <td>{{ $o->user->email }}</td>
                        <td>{{ $o->items->count() }}</td>
                        <td class="text-right font-semibold">{{ number_format($o->total_cents/100,2) }} {{ $o->currency }}</td>
                        <td class="text-right">
                            <span @class(['badge-green' => $o->isPaid(), 'badge-amber' => ! $o->isPaid()])>{{ ucfirst($o->status) }}</span>
                        </td>
                        <td class="text-right">
                            @if ($o->isPaid() && $o->refundedCents() < $o->total_cents)
                                <button wire:click="prepareRefund({{ $o->id }})" class="btn-ghost btn-sm" type="button">Refund</button>
                            @else
                                <span class="text-ink-500">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-ink-500">No orders.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
