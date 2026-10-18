<?php

namespace App\Livewire\Trainer;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrolment;
use Illuminate\Support\Carbon;
use Livewire\Component;

class Assignments extends Component
{
    public Course $course;

    public string $title = '';

    public string $instructions = '';

    public string $dueAt = '';

    public int $maxPoints = 100;

    public bool $isRequired = true;

    public string $evaluationType = 'numeric';

    public int $passingScore = 50;

    public bool $allowResubmission = true;

    public int $maxSubmissions = 2;

    public bool $allowLateSubmissions = false;

    public string $status = 'draft';

    public string $audience = 'course';

    public ?int $groupId = null;

    public array $recipientIds = [];

    public function mount(Course $course): void
    {
        $this->authorize('manageContent', $course);
    }

    public function create(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'dueAt' => 'nullable|date',
            'maxPoints' => 'required|integer|min:1',
            'isRequired' => 'boolean',
            'evaluationType' => 'required|in:numeric,pass_fail',
            'passingScore' => 'required_if:evaluationType,numeric|integer|min:0|max:100',
            'allowResubmission' => 'boolean',
            'maxSubmissions' => 'required|integer|min:1|max:20',
            'allowLateSubmissions' => 'boolean',
            'status' => 'required|in:draft,published',
            'audience' => 'required|in:course,group,students',
        ]);

        $assignment = Assignment::create([
            'course_id' => $this->course->id,
            'creator_id' => auth()->id(),
            'title' => $this->title,
            'instructions' => $this->instructions,
            'due_at' => $this->dueAt ? Carbon::parse($this->dueAt, auth()->user()->timezone ?? 'UTC')->utc() : null,
            'max_points' => $this->maxPoints,
            'is_required' => $this->isRequired,
            'evaluation_type' => $this->evaluationType,
            'passing_score' => $this->evaluationType === 'numeric' ? $this->passingScore : null,
            'allow_resubmission' => $this->allowResubmission,
            'max_submissions' => $this->maxSubmissions,
            'allow_late_submissions' => $this->allowLateSubmissions,
            'audience' => $this->audience,
            'group_id' => $this->audience === 'group' ? $this->groupId : null,
            'status' => $this->status,
        ]);

        if ($this->audience === 'students') {
            $assignment->recipients()->sync($this->recipientIds);
        }

        $this->reset(['title', 'instructions', 'dueAt', 'groupId', 'recipientIds']);
        session()->flash('saved', 'Assignment created.');
    }

    public function render()
    {
        return view('livewire.trainer.assignments', [
            'assignments' => Assignment::where('course_id', $this->course->id)
                ->withCount('submissions')->latest()->get(),
            'groups' => $this->course->groups()->get(),
            'students' => Enrolment::whereHas('variant', fn ($q) => $q->where('course_id', $this->course->id))
                ->where('status', 'active')->with('user')->get()->pluck('user'),
        ])->layout('layouts::app', ['title' => 'Assignments']);
    }
}
