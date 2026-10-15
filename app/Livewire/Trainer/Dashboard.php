<?php

namespace App\Livewire\Trainer;

use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.trainer.dashboard')
            ->layout('layouts::app', ['title' => 'Trainer Dashboard']);
    }
}
