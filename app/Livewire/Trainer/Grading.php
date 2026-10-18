<?php

namespace App\Livewire\Trainer;

use App\Domain\Certification\CertificateTrigger;
use App\Models\Assignment;
use App\Models\AssignmentGrade;
use App\Models\Enrolment;
use App\Notifications\StudentAccountNotification;
use Livewire\Component;

class Grading extends Component
{
    public Assignment $assignment;

    public array $scores = [];

    public array $feedbacks = [];

    public array $passed = [];

    public function mount(Assignment $assignment): void
    {
        $this->authorize('manageContent', $assignment->course);
    }

    public function grade(int $submissionId): void
    {
        $submission = $this->assignment->submissions()->findOrFail($submissionId);
        $this->authorize('grade', $submission);
        abort_unless($submission->status === 'submitted', 422, 'Only submitted work can be graded.');

        $isPassFail = $this->assignment->evaluation_type === 'pass_fail';
        $passed = $isPassFail
            ? (bool) ($this->passed[$submissionId] ?? false)
            : null;
        $score = $isPassFail
            ? ($passed ? $this->assignment->max_points : 0)
            : (int) ($this->scores[$submissionId] ?? 0);
        abort_if($score < 0 || $score > $this->assignment->max_points, 422, 'Score must be within the assignment range.');

        $feedback = $this->feedbacks[$submissionId] ?? null;
        $grade = AssignmentGrade::where('assignment_submission_id', $submissionId)->first();
        $changed = $grade === null || $grade->score !== $score || $grade->passed !== $passed || $grade->feedback !== $feedback || $grade->status !== 'released';

        AssignmentGrade::updateOrCreate(
            ['assignment_submission_id' => $submissionId],
            [
                'grader_id' => auth()->id(),
                'score' => $score,
                'passed' => $passed,
                'feedback' => $feedback,
                'status' => 'released',
            ]
        );
        $submission->update(['status' => 'graded']);
        $enrolment = Enrolment::where('user_id', $submission->user_id)
            ->where('status', 'active')
            ->whereHas('variant', fn ($q) => $q->where('course_id', $this->assignment->course_id))
            ->first();
        if ($enrolment) {
            CertificateTrigger::for($enrolment);
        }
        if ($changed) {
            $submission->user->notify(new StudentAccountNotification(
                'Assignment grade released',
                'Your grade for '.$this->assignment->title.' is now available.',
                route('student.results'),
                'assessments',
            ));
        }
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
