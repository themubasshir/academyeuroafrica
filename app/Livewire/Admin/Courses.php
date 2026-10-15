<?php

namespace App\Livewire\Admin;

use App\Models\Course;
use App\Models\Programme;
use Illuminate\Support\Str;
use Livewire\Component;

class Courses extends Component
{
    public string $title = '';

    public ?int $programmeId = null;

    public array $deliveryTypes = [];

    public function create(): void
    {
        $this->validate(['title' => 'required|string|max:255', 'programmeId' => 'required|exists:programmes,id']);
        Course::create([
            'programme_id' => $this->programmeId,
            'title' => $this->title,
            'slug' => Str::slug($this->title).'-'.Str::lower(Str::random(4)),
            'delivery_types' => $this->deliveryTypes ?: ['live'],
            'status' => 'draft',
        ]);
        $this->reset(['title', 'programmeId', 'deliveryTypes']);
    }

    public function toggleStatus(int $id): void
    {
        $c = Course::findOrFail($id);
        $c->update(['status' => $c->status === 'published' ? 'draft' : 'published']);
    }

    public function render()
    {
        return view('livewire.admin.courses', [
            'courses' => Course::with('programme', 'variants')->latest()->get(),
            'programmes' => Programme::orderBy('name')->get(),
        ])->layout('layouts::app', ['title' => 'Courses']);
    }
}
