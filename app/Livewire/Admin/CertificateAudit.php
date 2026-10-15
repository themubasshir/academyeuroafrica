<?php

namespace App\Livewire\Admin;

use App\Models\CertificateAudit;
use Livewire\Component;
use Livewire\WithPagination;

class CertificateAudit extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.admin.certificate-audit', [
            'logs' => CertificateAudit::with(['certificate', 'actor'])
                ->orderByDesc('id')->paginate(40),
        ])->layout('layouts::app', ['title' => 'Certificate Audit']);
    }
}
