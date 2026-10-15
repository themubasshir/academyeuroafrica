<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class Integrations extends Component
{
    public function render()
    {
        return view('livewire.admin.integrations', [
            'joomlaConfigured' => (bool) config('services.joomla.bridge_secret'),
            'stripeConfigured' => (bool) config('services.stripe.secret'),
            'gateway' => config('services.payments.default'),
            'mappings' => \DB::table('joomla_page_mappings')->get(),
        ])->layout('layouts::app', ['title' => 'Integrations']);
    }
}
