<?php

namespace App\Livewire\Admin;

use App\Models\Enrolment;
use Livewire\Component;

class Enrolments extends Component
{
    public function render()
    {
        return view('livewire.admin.enrolments', [
            'enrolments' => Enrolment::with('user', 'variant.course')->latest('enrolled_at')->limit(100)->get(),
        ])->layout('layouts::app', ['title' => 'Enrolments']);
    }
}
