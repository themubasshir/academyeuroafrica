<?php

namespace App\Livewire\Admin;

use App\Domain\Checkout\OrderService;
use App\Models\Order;
use Livewire\Component;

class Orders extends Component
{
    public ?int $refundOrderId = null;

    public string $refundAmount = '';

    public string $refundReason = '';

    public function prepareRefund(int $orderId): void
    {
        $order = Order::findOrFail($orderId);
        abort_unless($order->isPaid(), 422, 'Only paid orders can be refunded.');

        $this->refundOrderId = $orderId;
        $this->refundAmount = number_format(($order->total_cents - $order->refundedCents()) / 100, 2, '.', '');
        $this->refundReason = '';
    }

    public function refund(OrderService $orders): void
    {
        $data = $this->validate([
            'refundOrderId' => 'required|exists:orders,id',
            'refundAmount' => 'required|numeric|min:0.01',
            'refundReason' => 'nullable|string|max:255',
        ]);
        $order = Order::findOrFail($data['refundOrderId']);
        $amountCents = (int) round((float) $data['refundAmount'] * 100);
        abort_if($amountCents > $order->total_cents - $order->refundedCents(), 422, 'Refund exceeds the remaining balance.');

        $orders->refund($order, auth()->user(), $amountCents, $data['refundReason'] ?: null);
        $this->reset(['refundOrderId', 'refundAmount', 'refundReason']);
    }

    public function render()
    {
        return view('livewire.admin.orders', [
            'orders' => Order::with('user', 'items')->latest()->limit(100)->get(),
        ])->layout('layouts::app', ['title' => 'Orders']);
    }
}
