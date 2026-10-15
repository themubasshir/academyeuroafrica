<?php

namespace App\Livewire\Catalogue;

use App\Models\Programme;
use Livewire\Component;

class ProgrammeShow extends Component
{
    public Programme $programme;

    public function mount(Programme $programme): void
    {
        abort_unless($programme->status === 'published', 404);
    }

    public function render()
    {
        return view('livewire.catalogue.programme-show', [
            'courses' => $this->programme->courses()
                ->where('status', 'published')
                ->with(['variants' => fn ($q) => $q->where('status', 'published')])
                ->get(),
        ])->layout('layouts::app', ['title' => $this->programme->name]);
    }
}
