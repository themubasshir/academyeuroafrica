<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Material;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class Materials extends Component
{
    use WithPagination;

    public string $search = '';

    public string $visibility = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingVisibility(): void
    {
        $this->resetPage();
    }

    public function delete(int $materialId): void
    {
        $material = Material::findOrFail($materialId);
        $originalName = $material->original_name;
        Storage::disk($material->disk)->delete($material->path);
        AuditLog::record('material.deleted', $material, ['original_name' => $originalName]);
        $material->delete();
        session()->flash('saved', 'Material deleted.');
    }

    public function render()
    {
        return view('livewire.admin.materials', [
            'materials' => Material::query()
                ->with(['uploader', 'group', 'student', 'attachable'])
                ->when($this->search, fn ($query) => $query->where(fn ($search) => $search
                    ->where('original_name', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%")))
                ->when($this->visibility, fn ($query) => $query->where('visibility', $this->visibility))
                ->latest()
                ->paginate(25),
        ])->layout('layouts::app', ['title' => 'Materials']);
    }
}
