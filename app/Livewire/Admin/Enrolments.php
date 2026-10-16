<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\CourseVariant;
use App\Models\Enrolment;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class Enrolments extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public ?int $userId = null;

    public ?int $variantId = null;

    public ?string $expiresAt = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function grant(): void
    {
        $data = $this->validate([
            'userId' => 'required|exists:users,id',
            'variantId' => 'required|exists:course_variants,id',
            'expiresAt' => 'nullable|date',
        ]);
        $enrolment = Enrolment::firstOrNew(['user_id' => $data['userId'], 'course_variant_id' => $data['variantId']]);
        abort_if($enrolment->exists && $enrolment->order_id !== null, 422, 'A commercial enrolment cannot be changed manually. Use the order or refund workflow.');
        $wasNew = ! $enrolment->exists;
        $enrolment->fill(['status' => 'active', 'enrolled_at' => $enrolment->enrolled_at ?? now(), 'expires_at' => $data['expiresAt']]);
        $enrolment->save();
        AuditLog::record($wasNew ? 'enrolment.granted' : 'enrolment.restored', $enrolment);
        $this->reset(['userId', 'variantId', 'expiresAt']);
    }

    public function suspend(int $enrolmentId): void
    {
        $enrolment = Enrolment::findOrFail($enrolmentId);
        abort_if($enrolment->order_id !== null, 422, 'A commercial enrolment is managed by payment reconciliation.');
        $enrolment->update(['status' => 'suspended']);
        AuditLog::record('enrolment.suspended', $enrolment);
    }

    public function restore(int $enrolmentId): void
    {
        $enrolment = Enrolment::findOrFail($enrolmentId);
        abort_if($enrolment->order_id !== null, 422, 'A commercial enrolment is managed by payment reconciliation.');
        $enrolment->update(['status' => 'active']);
        AuditLog::record('enrolment.restored', $enrolment);
    }

    public function render()
    {
        return view('livewire.admin.enrolments', [
            'enrolments' => Enrolment::query()
                ->with('user', 'variant.course')
                ->when($this->search, fn ($query) => $query->whereHas('user', fn ($users) => $users->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")))
                ->when($this->status, fn ($query) => $query->where('status', $this->status))
                ->latest('enrolled_at')->paginate(25),
            'users' => User::where('status', 'active')->orderBy('name')->limit(250)->get(),
            'variants' => CourseVariant::with('course')->orderBy('name')->limit(250)->get(),
        ])->layout('layouts::app', ['title' => 'Enrolments']);
    }
}
