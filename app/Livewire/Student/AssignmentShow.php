<?php

namespace App\Livewire\Student;

use App\Domain\Files\FileService;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Enrolment;
use Livewire\Component;
use Livewire\WithFileUploads;

class AssignmentShow extends Component
{
    use WithFileUploads;

    public Enrolment $enrolment;

    public Assignment $assignment;

    public string $body = '';

    public $upload;

    public ?AssignmentSubmission $submission = null;

    public function mount(Enrolment $enrolment, Assignment $assignment): void
    {
        $this->authorize('view', $enrolment);
        abort_unless($enrolment->isActive(), 403, 'This enrolment has expired.');
        abort_unless($assignment->course_id === $enrolment->variant->course_id, 404);
        abort_unless($assignment->status === 'published', 404);
        abort_unless($assignment->isRecipient(auth()->user()), 403);

        $this->submission = AssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('user_id', auth()->id())
            ->latest('attempt_no')
            ->first()
            ?? AssignmentSubmission::create([
                'assignment_id' => $assignment->id,
                'user_id' => auth()->id(),
                'attempt_no' => 1,
                'status' => 'draft',
            ]);
        $this->body = (string) $this->submission->body;
    }

    public function save(FileService $files): void
    {
        abort_unless($this->enrolment->isActive(), 403);
        abort_if($this->assignment->due_at?->isPast() && ! $this->assignment->allow_late_submissions, 422, 'The submission deadline has passed.');
        abort_unless(! in_array($this->submission->status, ['graded', 'submitted'], true), 422, 'Submission is already awaiting or has received grading.');

        if ($this->submission->status === 'returned') {
            $attemptCount = AssignmentSubmission::where('assignment_id', $this->assignment->id)
                ->where('user_id', auth()->id())
                ->count();
            abort_unless($this->assignment->allow_resubmission, 422, 'Resubmission is not allowed.');
            abort_if($attemptCount >= $this->assignment->max_submissions, 422, 'Submission limit reached.');
            $this->submission = AssignmentSubmission::create([
                'assignment_id' => $this->assignment->id,
                'user_id' => auth()->id(),
                'attempt_no' => $attemptCount + 1,
                'status' => 'draft',
            ]);
        }

        $this->validate([
            'body' => ['nullable', 'string', 'max:50000'],
            'upload' => ['nullable', 'file', 'max:25600'],
        ]);
        abort_if(blank($this->body) && $this->upload === null, 422, 'Provide a written response or file.');

        $this->submission->update([
            'body' => $this->body,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        if ($this->upload) {
            $files->storeSubmissionFile($this->upload, $this->submission);
            $this->reset('upload');
        }

        $this->submission->refresh();
        session()->flash('saved', 'Submission sent for grading.');
    }

    public function render()
    {
        return view('livewire.student.assignment-show', [
            'grade' => $this->submission->grade,
            'overdue' => $this->assignment->due_at?->isPast() ?? false,
            'history' => AssignmentSubmission::where('assignment_id', $this->assignment->id)
                ->where('user_id', auth()->id())
                ->with(['grade', 'attachments'])
                ->latest('attempt_no')
                ->get(),
        ])->layout('layouts::app', ['title' => $this->assignment->title]);
    }
}
