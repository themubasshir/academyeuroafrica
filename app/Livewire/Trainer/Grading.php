<?php

namespace App\Livewire\Trainer;

use App\Models\Assignment;
use App\Models\AssignmentGrade;
use Livewire\Component;

class Grading extends Component
{
    public Assignment $assignment;

    public array $scores = [];

    public array $feedbacks = [];

    public function mount(Assignment $assignment): void
    {
        $this->authorize('manageContent', $assignment->course);
    }

    public function grade(int $submissionId): void
    {
        $submission = $this->assignment->submissions()->findOrFail($submissionId);
        $this->authorize('grade', $submission);

        $score = (int) ($this->scores[$submissionId] ?? 0);
        abort_if($score > $this->assignment->max_points, 422, 'Score exceeds maximum.');

        AssignmentGrade::updateOrCreate(
            ['assignment_submission_id' => $submissionId],
            [
                'grader_id' => auth()->id(),
                'score' => $score,
                'feedback' => $this->feedbacks[$submissionId] ?? null,
                'status' => 'released',
            ]
        );
        $submission->update(['status' => 'graded']);
        session()->flash('saved', 'Grade released.');
    }

    public function returnForRevision(int $submissionId): void
    {
        $submission = $this->assignment->submissions()->findOrFail($submissionId);
        $this->authorize('grade', $submission);
        $submission->update(['status' => 'returned']);
        AssignmentGrade::where('assignment_submission_id', $submissionId)->update(['status' => 'returned']);
    }

    public function render()
    {
        return view('livewire.trainer.grading', [
            'submissions' => $this->assignment->submissions()->with(['user', 'grade', 'attachments'])->get(),
        ])->layout('layouts::app', ['title' => 'Grade: '.$this->assignment->title]);
    }
}
