<?php

namespace App\Livewire\Admin;

use App\Services\Payments\Gateways\PayPalGateway;
use Livewire\Component;

class Integrations extends Component
{
    public function render()
    {
        return view('livewire.admin.integrations', [
            'joomlaConfigured' => (bool) config('services.joomla.bridge_secret'),
            'paypalConfigured' => app(PayPalGateway::class)->isConfigured(),
            'paypalMode' => app(PayPalGateway::class)->environment(),
            'gateway' => config('services.payments.default'),
            'mappings' => \DB::table('joomla_page_mappings')->get(),
        ])->layout('layouts::app', ['title' => 'Integrations']);
    }
}
