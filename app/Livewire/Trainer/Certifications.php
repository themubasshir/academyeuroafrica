<?php

namespace App\Livewire\Trainer;

use App\Domain\Certification\CertificateIssuanceService;
use App\Domain\Certification\CertificateTrigger;
use App\Domain\Certification\CertificationEligibilityService;
use App\Models\Course;
use App\Models\Enrolment;
use App\Models\EnrolmentApproval;
use Livewire\Component;

class Certifications extends Component
{
    public Course $course;

    public function mount(Course $course): void
    {
        $this->authorize('manageContent', $course);
    }

    public function approve(int $enrolmentId): void
    {
        $enrolment = $this->scopedEnrolment($enrolmentId);
        EnrolmentApproval::firstOrCreate(
            ['enrolment_id' => $enrolment->id, 'kind' => 'trainer'],
            ['approver_id' => auth()->id()]
        );
        CertificateTrigger::for($enrolment);
        session()->flash('saved', 'Completion approved.');
    }

    public function requestReevaluation(int $enrolmentId, CertificateIssuanceService $service): void
    {
        $enrolment = $this->scopedEnrolment($enrolmentId);
        $service->issueIfEligible($enrolment, auth()->user());
        session()->flash('saved', 'Re-evaluation requested.');
    }

    private function scopedEnrolment(int $id): Enrolment
    {
        return Enrolment::where('id', $id)
            ->whereHas('variant', fn ($q) => $q->where('course_id', $this->course->id))
            ->firstOrFail();
    }

    public function render(CertificationEligibilityService $eligibility)
    {
        $enrolments = Enrolment::whereHas('variant', fn ($q) => $q->where('course_id', $this->course->id))
            ->where('status', 'active')
            ->with(['user', 'certificate' => fn ($q) => $q->whereNotNull('active_key')])
            ->get();

        $rows = $enrolments->map(fn ($e) => [
            'enrolment' => $e,
            'result' => $eligibility->evaluate($e),
        ]);

        return view('livewire.trainer.certifications', compact('rows'))
            ->layout('layouts::app', ['title' => 'Certifications: '.$this->course->title]);
    }
}
