<?php

namespace App\Livewire\Admin;

use App\Models\CertificationPolicy;
use App\Models\Course;
use Livewire\Component;

class CertificatePolicy extends Component
{
    public Course $course;

    public array $form = [];

    public function mount(Course $course): void
    {
        $this->course = $course;
        $p = $course->certificationPolicy;

        $this->form = [
            'enabled' => $p?->enabled ?? false,
            'require_lessons' => $p?->require_lessons ?? true,
            'require_quizzes' => $p?->require_quizzes ?? true,
            'min_quiz_percent' => $p?->min_quiz_percent,
            'require_assignments' => $p?->require_assignments ?? true,
            'min_assignment_percent' => $p?->min_assignment_percent,
            'min_attendance_percent' => $p?->min_attendance_percent,
            'require_trainer_approval' => $p?->require_trainer_approval ?? false,
            'require_admin_approval' => $p?->require_admin_approval ?? false,
            'auto_issue' => $p?->auto_issue ?? true,
            'signatory_name' => $p?->signatory_name,
            'signatory_title' => $p?->signatory_title,
        ];
    }

    public function save(): void
    {
        $data = $this->validate([
            'form.enabled' => 'boolean',
            'form.require_lessons' => 'boolean',
            'form.require_quizzes' => 'boolean',
            'form.min_quiz_percent' => 'nullable|integer|min:0|max:100',
            'form.require_assignments' => 'boolean',
            'form.min_assignment_percent' => 'nullable|integer|min:0|max:100',
            'form.min_attendance_percent' => 'nullable|integer|min:0|max:100',
            'form.require_trainer_approval' => 'boolean',
            'form.require_admin_approval' => 'boolean',
            'form.auto_issue' => 'boolean',
            'form.signatory_name' => 'nullable|string|max:120',
            'form.signatory_title' => 'nullable|string|max:120',
        ])['form'];

        CertificationPolicy::updateOrCreate(['course_id' => $this->course->id], $data);
        session()->flash('saved', 'Certification policy saved.');
    }

    public function render()
    {
        return view('livewire.admin.certificate-policy')
            ->layout('layouts::app', ['title' => 'Policy: '.$this->course->title]);
    }
}
