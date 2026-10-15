<?php

namespace App\Livewire\Student;

use App\Domain\Certification\CertificationEligibilityService;
use App\Models\Certificate;
use App\Models\Enrolment;
use Livewire\Component;

class Certificates extends Component
{
    public function render(CertificationEligibilityService $eligibility)
    {
        $user = auth()->user();

        $certs = Certificate::where('user_id', $user->id)
            ->with('course')
            ->orderByDesc('issued_at')->get();

        // Eligibility per active enrolment on a certifiable course.
        $progress = Enrolment::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('variant.course.certificationPolicy')
            ->get()
            ->filter(fn ($e) => $e->variant->course->certificationPolicy?->enabled)
            ->map(fn ($e) => [
                'enrolment' => $e,
                'result' => $eligibility->evaluate($e),
                'issued' => $certs->firstWhere('enrolment_id', $e->id),
            ]);

        return view('livewire.student.certificates', compact('certs', 'progress'))
            ->layout('layouts::app', ['title' => 'My Certificates']);
    }
}
