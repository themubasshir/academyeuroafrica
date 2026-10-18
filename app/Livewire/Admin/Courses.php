<?php

namespace App\Livewire\Admin;

use App\Models\Course;
use App\Models\Programme;
use App\Models\TrainerAssignment;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Courses extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public bool $showCourseModal = false;

    public string $title = '';

    public ?int $programmeId = null;

    public string $description = '';

    public string $learningOutcomes = '';

    public string $status = 'draft';

    public string $deliveryTypes = '';

    public ?int $accessDays = null;

    public int $sortOrder = 0;

    public ?int $trainerId = null;

    public function save(): void
    {
        $data = $this->validate([
            'title' => 'required|string|max:255',
            'programmeId' => 'required|exists:programmes,id',
            'description' => 'nullable|string|max:10000',
            'learningOutcomes' => 'nullable|string|max:10000',
            'status' => 'required|in:draft,published,archived',
            'deliveryTypes' => 'nullable|string|max:255',
            'accessDays' => 'nullable|integer|min:1|max:3650',
            'sortOrder' => 'required|integer|min:0',
            'trainerId' => 'nullable|exists:users,id',
        ]);

        $course = $this->editingId ? Course::findOrFail($this->editingId) : new Course;
        $course->fill([
            'programme_id' => $data['programmeId'],
            'title' => $data['title'],
            'description' => $data['description'] ?: null,
            'learning_outcomes' => $data['learningOutcomes'] ? array_values(array_filter(array_map('trim', explode("\n", $data['learningOutcomes'])), static fn (string $outcome): bool => $outcome !== '')) : null,
            'status' => $data['status'],
            'delivery_types' => array_values(array_filter(array_map('trim', explode(',', $data['deliveryTypes'] ?? '')))),
            'access_days' => $data['accessDays'],
            'sort_order' => $data['sortOrder'],
        ]);
        if (! $course->exists) {
            $course->slug = Str::slug($course->title).'-'.Str::lower(Str::random(4));
        }
        $course->save();

        if ($this->trainerId) {
            TrainerAssignment::updateOrCreate(['course_id' => $course->id, 'trainer_id' => $this->trainerId]);
        }
        session()->flash('saved', $this->editingId ? 'Course updated.' : 'Course created.');
        $this->closeCourseModal();
    }

    public function createCourse(): void
    {
        $this->resetForm();
        $this->showCourseModal = true;
    }

    public function editCourse(int $courseId): void
    {
        $this->resetForm();
        $course = Course::findOrFail($courseId);
        $this->editingId = $course->id;
        $this->title = $course->title;
        $this->programmeId = $course->programme_id;
        $this->description = (string) $course->description;
        $this->learningOutcomes = implode("\n", $course->learning_outcomes ?? []);
        $this->status = $course->status;
        $this->deliveryTypes = implode(', ', $course->delivery_types ?? []);
        $this->accessDays = $course->access_days;
        $this->sortOrder = $course->sort_order;
        $this->trainerId = $course->trainerAssignments()->value('trainer_id');
        $this->showCourseModal = true;
    }

    public function archive(int $courseId): void
    {
        $course = Course::findOrFail($courseId);
        $course->update(['status' => 'archived']);
    }

    public function assignCheckoutPackage(int $courseId, mixed $variantId): void
    {
        $course = Course::findOrFail($courseId);

        if ($variantId === null || $variantId === '') {
            $course->update(['checkout_variant_id' => null]);

            return;
        }

        $variant = $course->variants()->findOrFail((int) $variantId);
        abort_unless(
            $variant->availability === 'paid'
                && $variant->status === 'published'
                && $variant->approval_status === 'approved'
                && $variant->price_cents !== null,
            422,
            'Choose an approved, published package with a fixed price.'
        );

        $course->update(['checkout_variant_id' => $variant->id]);
    }

    public function closeCourseModal(): void
    {
        $this->showCourseModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'title', 'programmeId', 'description', 'learningOutcomes', 'deliveryTypes', 'accessDays', 'sortOrder', 'trainerId']);
        $this->status = 'draft';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.courses', [
            'courses' => Course::with(['programme', 'variants', 'checkoutVariant.course'])
                ->withCount(['modules', 'lessons'])
                ->latest()
                ->paginate(25),
            'programmes' => Programme::orderBy('name')->get(),
            'trainers' => User::whereHas('roles', fn ($query) => $query->where('name', 'trainer'))->orderBy('name')->get(),
        ])->layout('layouts::app', ['title' => 'Courses']);
    }
}
