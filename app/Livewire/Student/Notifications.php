<?php

namespace App\Livewire\Student;

use Livewire\Component;

class Notifications extends Component
{
    public function markAsRead(string $id): void
    {
        auth()->user()->notifications()->whereKey($id)->update(['read_at' => now()]);
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
    }

    public function render()
    {
        return view('livewire.student.notifications', [
            'notifications' => auth()->user()->notifications()->latest()->paginate(15),
        ])->layout('layouts::app', ['title' => 'Notifications']);
    }
}
