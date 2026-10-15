<?php

namespace App\Livewire\Admin;

use App\Models\Role;
use App\Models\User;
use Livewire\Component;

class Users extends Component
{
    public string $search = '';

    public function toggleActive(int $id): void
    {
        $user = User::findOrFail($id);
        $user->update([
            'status' => $user->status === 'disabled' ? 'active' : 'disabled',
            'disabled_at' => $user->status === 'disabled' ? null : now(),
        ]);
    }

    public function render()
    {
        return view('livewire.admin.users', [
            'users' => User::when($this->search, fn ($q) => $q->where('email', 'like', "%{$this->search}%")->orWhere('name', 'like', "%{$this->search}%"))
                ->with('roles')->latest()->limit(50)->get(),
            'roles' => Role::all(),
        ])->layout('layouts::app', ['title' => 'Users']);
    }
}
