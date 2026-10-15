<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class Integrations extends Component
{
    public function render()
    {
        return view('livewire.admin.integrations', [
            'joomlaConfigured' => (bool) config('services.joomla.bridge_secret'),
            'paypalConfigured' => (bool) config('services.paypal.client_id'),
            'paypalMode' => config('services.paypal.mode', 'sandbox'),
            'gateway' => config('services.payments.default'),
            'mappings' => \DB::table('joomla_page_mappings')->get(),
        ])->layout('layouts::app', ['title' => 'Integrations']);
    }
}
