<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use Livewire\Component;

class AuditLogs extends Component
{
    public function render()
    {
        return view('livewire.admin.audit-logs', [
            'logs' => AuditLog::with('actor')->latest()->limit(200)->get(),
        ])->layout('layouts::app', ['title' => 'Audit Logs']);
    }
}
