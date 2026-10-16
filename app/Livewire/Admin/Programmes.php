<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Programme;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Programmes extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public string $status = 'draft';

    public int $sortOrder = 0;

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'status' => 'required|in:draft,published,archived',
            'sortOrder' => 'required|integer|min:0',
        ]);

        $programme = $this->editingId ? Programme::findOrFail($this->editingId) : new Programme;
        $programme->fill([
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'status' => $data['status'],
            'sort_order' => $data['sortOrder'],
        ]);
        if (! $programme->exists) {
            $programme->slug = Str::slug($programme->name).'-'.Str::lower(Str::random(4));
        }
        $programme->save();
        AuditLog::record($this->editingId ? 'programme.updated' : 'programme.created', $programme);
        $this->resetForm();
    }

    public function edit(int $programmeId): void
    {
        $programme = Programme::findOrFail($programmeId);
        $this->editingId = $programme->id;
        $this->name = $programme->name;
        $this->description = (string) $programme->description;
        $this->status = $programme->status;
        $this->sortOrder = $programme->sort_order;
    }

    public function archive(int $programmeId): void
    {
        $programme = Programme::findOrFail($programmeId);
        $programme->update(['status' => 'archived']);
        AuditLog::record('programme.archived', $programme);
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'description', 'sortOrder']);
        $this->status = 'draft';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.programmes', [
            'programmes' => Programme::withCount('courses')->orderBy('sort_order')->paginate(25),
        ])->layout('layouts::app', ['title' => 'Programmes']);
    }
}
