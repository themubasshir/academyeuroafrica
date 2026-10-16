<?php

namespace App\Livewire\Student;

use App\Models\Order;
use Livewire\Component;

class Orders extends Component
{
    public function render()
    {
        return view('livewire.student.orders', [
            'orders' => Order::where('user_id', auth()->id())
                ->with(['items.variant.course', 'transactions.refunds', 'items.variant.course'])
                ->latest()->paginate(10),
        ])->layout('layouts::app', ['title' => 'Order History']);
    }
}
