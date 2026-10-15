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
        abort_unless($assignment->course_id === $enrolment->variant->course_id, 404);
        abort_unless($assignment->status === 'published', 404);
        abort_unless($assignment->isRecipient(auth()->user()), 403);

        $this->submission = AssignmentSubmission::firstOrCreate(
            ['assignment_id' => $assignment->id, 'user_id' => auth()->id()],
            ['status' => 'draft']
        );
        $this->body = (string) $this->submission->body;
    }

    public function save(FileService $files): void
    {
        abort_unless($this->submission->status !== 'graded', 422, 'Already graded.');

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
        session()->flash('saved', 'Submission saved.');
    }

    public function render()
    {
        return view('livewire.student.assignment-show', [
            'grade' => $this->submission->grade,
            'overdue' => $this->assignment->due_at?->isPast() ?? false,
        ])->layout('layouts::app', ['title' => $this->assignment->title]);
    }
}
