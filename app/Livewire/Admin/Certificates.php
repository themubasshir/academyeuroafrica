<?php

namespace App\Livewire\Admin;

use App\Domain\Certification\CertificateIssuanceService;
use App\Models\Certificate;
use App\Models\Enrolment;
use Livewire\Component;
use Livewire\WithPagination;

class Certificates extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    public ?int $revokingId = null;

    public string $revokeReason = '';

    public function startRevoke(int $id): void
    {
        $this->revokingId = $id;
        $this->revokeReason = '';
    }

    public function revoke(CertificateIssuanceService $service): void
    {
        $this->validate(['revokeReason' => 'required|string|min:5|max:500']);
        $cert = Certificate::findOrFail($this->revokingId);
        $service->revoke($cert, auth()->user(), $this->revokeReason);
        $this->reset(['revokingId', 'revokeReason']);
        session()->flash('saved', "Certificate {$cert->number} revoked.");
    }

    public function manualIssue(int $enrolmentId, CertificateIssuanceService $service): void
    {
        $enrolment = Enrolment::findOrFail($enrolmentId);
        $service->issueIfEligible($enrolment, auth()->user(), force: true);
        session()->flash('saved', 'Issued (or re-confirmed) certificate.');
    }

    public function render()
    {
        $certs = Certificate::with(['user', 'course'])
            ->when($this->search, fn ($q) => $q->where(function ($x) {
                $x->where('number', 'like', "%{$this->search}%")
                    ->orWhere('recipient_name', 'like', "%{$this->search}%")
                    ->orWhere('course_title', 'like', "%{$this->search}%");
            }))
            ->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))
            ->orderByDesc('issued_at')
            ->paginate(20);

        // Enrolments on certifiable courses with no active cert — reconciliation view.
        $missing = Enrolment::where('status', 'active')
            ->whereHas('variant.course.certificationPolicy', fn ($q) => $q->where('enabled', true))
            ->whereDoesntHave('certificate', fn ($q) => $q->whereNotNull('active_key'))
            ->with(['user', 'variant.course'])
            ->limit(50)->get();

        return view('livewire.admin.certificates', compact('certs', 'missing'))
            ->layout('layouts::app', ['title' => 'Certificates']);
    }
}
