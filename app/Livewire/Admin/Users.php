<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Role;
use App\Models\TrainerAssignment;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Users extends Component
{
    use WithPagination;

    public string $search = '';

    public string $role = '';

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $timezone = '';

    public array $selectedRoles = [];

    public array $trainerCourses = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRole(): void
    {
        $this->resetPage();
    }

    public function edit(int $userId): void
    {
        $user = User::with(['roles', 'trainerAssignments'])->findOrFail($userId);
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->timezone = (string) $user->timezone;
        $this->selectedRoles = $user->roles->pluck('name')->all();
        $this->trainerCourses = $user->trainerAssignments->pluck('course_id')->map(fn ($id) => (string) $id)->all();
    }

    public function save(): void
    {
        $user = User::findOrFail($this->editingId);
        $data = $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'timezone' => 'nullable|timezone',
            'selectedRoles' => 'array',
            'selectedRoles.*' => 'exists:roles,name',
            'trainerCourses' => 'array',
            'trainerCourses.*' => 'exists:courses,id',
        ]);

        $isSuperAdmin = $user->roles()->where('name', Role::SUPER_ADMIN)->exists();
        $removingLastSuperAdmin = $isSuperAdmin
            && ! in_array(Role::SUPER_ADMIN, $data['selectedRoles'], true)
            && User::whereHas('roles', fn ($query) => $query->where('name', Role::SUPER_ADMIN))->count() <= 1;
        abort_if($removingLastSuperAdmin, 422, 'The last super-admin cannot be removed.');

        $user->update(['name' => $data['name'], 'email' => $data['email'], 'timezone' => $data['timezone'] ?: null]);
        $roleIds = Role::whereIn('name', $data['selectedRoles'])->pluck('id');
        $user->roles()->sync($roleIds);
        TrainerAssignment::where('trainer_id', $user->id)->delete();
        if (in_array(Role::TRAINER, $data['selectedRoles'], true)) {
            foreach ($data['trainerCourses'] as $courseId) {
                TrainerAssignment::firstOrCreate(['trainer_id' => $user->id, 'course_id' => $courseId]);
            }
        }
        AuditLog::record('user.updated', $user, ['roles' => $data['selectedRoles'], 'trainer_courses' => $data['trainerCourses']]);
        $this->resetForm();
    }

    public function toggleActive(int $userId): void
    {
        $user = User::findOrFail($userId);
        $isDisabling = $user->status !== 'disabled';
        $isSuperAdmin = $user->roles()->where('name', Role::SUPER_ADMIN)->exists();
        abort_if($isDisabling && $isSuperAdmin
            && User::whereHas('roles', fn ($query) => $query->where('name', Role::SUPER_ADMIN))->count() <= 1,
            422,
            'The last super-admin cannot be disabled.');

        $user->update([
            'status' => $isDisabling ? 'disabled' : 'active',
            'disabled_at' => $isDisabling ? now() : null,
        ]);
        AuditLog::record($isDisabling ? 'user.disabled' : 'user.enabled', $user);
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'email', 'timezone', 'selectedRoles', 'trainerCourses']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.users', [
            'users' => User::query()
                ->when($this->search, fn ($query) => $query->where(fn ($search) => $search->where('email', 'like', "%{$this->search}%")->orWhere('name', 'like', "%{$this->search}%")))
                ->when($this->role, fn ($query) => $query->whereHas('roles', fn ($roles) => $roles->where('name', $this->role)))
                ->with('roles')->latest()->paginate(25),
            'roles' => Role::orderBy('label')->get(),
            'courses' => Course::orderBy('title')->get(),
        ])->layout('layouts::app', ['title' => 'Users']);
    }
}
