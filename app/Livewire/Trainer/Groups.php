<?php

namespace App\Livewire\Trainer;

use App\Models\Course;
use App\Models\Enrolment;
use App\Models\Group;
use Livewire\Component;

class Groups extends Component
{
    public Course $course;

    public string $name = '';

    public function mount(Course $course): void
    {
        $this->authorize('manageContent', $course);
    }

    public function create(): void
    {
        $this->validate(['name' => 'required|string|max:255']);
        Group::create([
            'course_id' => $this->course->id,
            'trainer_id' => auth()->id(),
            'name' => $this->name,
        ]);
        $this->reset('name');
    }

    public function toggleMember(int $groupId, int $userId): void
    {
        $group = $this->course->groups()->findOrFail($groupId);
        $group->members()->toggle([$userId]);
    }

    public function deleteGroup(int $id): void
    {
        $this->course->groups()->where('id', $id)->delete();
    }

    public function render()
    {
        return view('livewire.trainer.groups', [
            'groups' => $this->course->groups()->with('members')->get(),
            'students' => Enrolment::whereHas('variant', fn ($q) => $q->where('course_id', $this->course->id))
                ->where('status', 'active')->with('user')->get()->pluck('user'),
        ])->layout('layouts::app', ['title' => 'Groups']);
    }
}
