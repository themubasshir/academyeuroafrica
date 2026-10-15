<?php

namespace App\Livewire\Student;

use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $enrolments = auth()->user()->enrolments()
            ->with('variant.course')
            ->latest('enrolled_at')
            ->get();

        return view('livewire.student.dashboard', compact('enrolments'))
            ->layout('layouts::app', ['title' => 'My Courses']);
    }
}
