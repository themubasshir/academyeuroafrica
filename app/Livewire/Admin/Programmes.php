<?php

namespace App\Livewire\Admin;

use App\Models\Programme;
use Illuminate\Support\Str;
use Livewire\Component;

class Programmes extends Component
{
    public string $name = '';

    public function create(): void
    {
        $this->validate(['name' => 'required|string|max:255']);
        Programme::create([
            'name' => $this->name,
            'slug' => Str::slug($this->name).'-'.Str::lower(Str::random(4)),
            'status' => 'published',
        ]);
        $this->reset('name');
    }

    public function toggleStatus(int $id): void
    {
        $p = Programme::findOrFail($id);
        $p->update(['status' => $p->status === 'published' ? 'draft' : 'published']);
    }

    public function render()
    {
        return view('livewire.admin.programmes', [
            'programmes' => Programme::withCount('courses')->get(),
        ])->layout('layouts::app', ['title' => 'Programmes']);
    }
}
