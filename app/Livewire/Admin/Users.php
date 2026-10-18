<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\CourseVariant;
use App\Models\Enrolment;
use App\Models\Role;
use App\Models\TrainerAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
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

    public array $studentVariants = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRole(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->guardAdmin();
        $this->resetForm();
        $this->selectedRoles = [Role::STUDENT];
        $this->editingId = 0;
    }

    public function edit(int $userId): void
    {
        $this->guardAdmin();
        $user = User::with(['roles', 'trainerAssignments', 'enrolments'])->findOrFail($userId);
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->timezone = (string) $user->timezone;
        $this->selectedRoles = $user->roles->pluck('name')->all();
        $this->trainerCourses = $user->trainerAssignments->pluck('course_id')->map(fn ($id) => (string) $id)->all();
        $this->studentVariants = $user->enrolments->pluck('course_variant_id')->map(fn ($id) => (string) $id)->all();
    }

    public function save(): void
    {
        $this->guardAdmin();
        $isCreating = $this->editingId === 0;
        $user = $isCreating ? new User : User::findOrFail($this->editingId);
        $this->selectedRoles = array_values(array_unique($this->selectedRoles));
        $data = $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'timezone' => 'nullable|timezone',
            'selectedRoles' => 'required|array|min:1',
            'selectedRoles.*' => Rule::in([Role::ACADEMY_ADMIN, Role::TRAINER, Role::STUDENT, Role::SUPER_ADMIN]),
            'trainerCourses' => 'array',
            'trainerCourses.*' => 'exists:courses,id',
            'studentVariants' => 'array',
            'studentVariants.*' => 'exists:course_variants,id',
        ]);
        $current = auth()->user();
        abort_if(! $current->hasRole(Role::SUPER_ADMIN) && in_array(Role::SUPER_ADMIN, $data['selectedRoles'], true), 403, 'Only a super-admin may assign super-admin role.');
        if (! $isCreating) {
            $isSuperAdmin = $user->roles()->where('name', Role::SUPER_ADMIN)->exists();
            abort_if($isSuperAdmin && ! in_array(Role::SUPER_ADMIN, $data['selectedRoles'], true)
                && User::whereHas('roles', fn ($query) => $query->where('name', Role::SUPER_ADMIN))->count() <= 1,
                422, 'The last super-admin cannot be removed.');
        }

        $email = strtolower(trim($data['email']));
        DB::transaction(function () use ($data, $email, $user, $isCreating): void {
            $user->fill([
                'name' => $data['name'],
                'email' => $email,
                'timezone' => $data['timezone'] ?: null,
                'status' => $user->status ?: 'active',
            ]);
            if ($isCreating) {
                $user->password = null;
            }
            $user->save();
            $roleIds = collect($data['selectedRoles'])->map(fn (string $name): int => Role::firstOrCreate(['name' => $name], ['label' => ucfirst(str_replace('-', ' ', $name))])->id);
            $user->roles()->sync($roleIds);
            $assignedCourseIds = TrainerAssignment::where('trainer_id', $user->id)->pluck('course_id')->map(fn ($id) => (int) $id)->all();
            $newTrainerCourseIds = in_array(Role::TRAINER, $data['selectedRoles'], true) ? array_map('intval', $data['trainerCourses']) : [];
            $removedTrainerCourseIds = array_values(array_diff($assignedCourseIds, $newTrainerCourseIds));
            TrainerAssignment::where('trainer_id', $user->id)->whereIn('course_id', $removedTrainerCourseIds)->delete();
            foreach ($newTrainerCourseIds as $courseId) {
                TrainerAssignment::firstOrCreate(['trainer_id' => $user->id, 'course_id' => $courseId]);
            }
            $assignedVariantIds = Enrolment::where('user_id', $user->id)->where('source', 'manual')->pluck('course_variant_id')->map(fn ($id) => (int) $id)->all();
            $newVariantIds = in_array(Role::STUDENT, $data['selectedRoles'], true) ? array_map('intval', $data['studentVariants']) : [];
            $removedVariantIds = array_values(array_diff($assignedVariantIds, $newVariantIds));
            $removedEnrolments = Enrolment::where('user_id', $user->id)->where('source', 'manual')->whereIn('course_variant_id', $removedVariantIds)->get();
            foreach ($removedEnrolments as $enrolment) {
                abort_if($enrolment->order_id !== null, 422, 'A commercial enrolment cannot be changed manually.');
                $enrolment->update(['status' => 'suspended', 'grant_reason' => $enrolment->grant_reason]);
                AuditLog::record('enrolment.revoked', $enrolment);
            }
            foreach ($newVariantIds as $variantId) {
                $enrolment = Enrolment::firstOrNew(['user_id' => $user->id, 'course_variant_id' => $variantId]);
                abort_if($enrolment->exists && $enrolment->order_id !== null, 422, 'A commercial enrolment cannot be changed manually.');
                $enrolment->fill(['status' => 'active', 'source' => 'manual', 'grant_reason' => 'Administrator membership assignment', 'enrolled_at' => $enrolment->enrolled_at ?? now()]);
                $enrolment->save();
            }
            if ($removedTrainerCourseIds !== []) {
                AuditLog::record('trainer.assignments.removed', $user, ['course_ids' => $removedTrainerCourseIds]);
            }
            if ($newTrainerCourseIds !== []) {
                AuditLog::record('trainer.assignments.updated', $user, ['course_ids' => $newTrainerCourseIds]);
            }
            AuditLog::record($isCreating ? 'user.created' : 'user.updated', $user, ['roles' => $data['selectedRoles'], 'trainer_courses' => $data['trainerCourses'], 'student_variants' => $data['studentVariants']]);
        });
        if ($isCreating) {
            Password::sendResetLink(['email' => $email]);
            AuditLog::record('user.invitation.sent', $user);
        }
        $this->resetForm();
    }

    public function resendInvitation(int $userId): void
    {
        $this->guardAdmin();
        $user = User::findOrFail($userId);
        abort_if($user->externalIdentities()->exists(), 422, 'Joomla-linked accounts use their external identity flow.');
        Password::sendResetLink(['email' => $user->email]);
        AuditLog::record('user.invitation.sent', $user);
    }

    public function toggleActive(int $userId): void
    {
        $this->guardAdmin();
        $user = User::findOrFail($userId);
        $isDisabling = $user->status !== 'disabled';
        $isSuperAdmin = $user->roles()->where('name', Role::SUPER_ADMIN)->exists();
        abort_if($isDisabling && $isSuperAdmin && User::whereHas('roles', fn ($query) => $query->where('name', Role::SUPER_ADMIN))->count() <= 1, 422, 'The last super-admin cannot be disabled.');
        $user->update(['status' => $isDisabling ? 'disabled' : 'active', 'disabled_at' => $isDisabling ? now() : null]);
        AuditLog::record($isDisabling ? 'user.disabled' : 'user.enabled', $user);
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'email', 'timezone', 'selectedRoles', 'trainerCourses', 'studentVariants']);
        $this->resetValidation();
    }

    private function guardAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function render()
    {
        return view('livewire.admin.users', [
            'users' => User::query()->when($this->search, fn ($query) => $query->where(fn ($search) => $search->where('email', 'like', "%{$this->search}%")->orWhere('name', 'like', "%{$this->search}%")))->when($this->role, fn ($query) => $query->whereHas('roles', fn ($roles) => $roles->where('name', $this->role)))->with('roles')->latest()->paginate(25),
            'roles' => Role::orderBy('label')->get(),
            'courses' => Course::orderBy('title')->get(),
            'variants' => CourseVariant::with('course')->orderBy('name')->get(),
        ])->layout('layouts::app', ['title' => 'Users']);
    }
}
