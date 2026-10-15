<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use Livewire\Component;

class Orders extends Component
{
    public function render()
    {
        return view('livewire.admin.orders', [
            'orders' => Order::with('user', 'items')->latest()->limit(100)->get(),
        ])->layout('layouts::app', ['title' => 'Orders']);
    }
}
