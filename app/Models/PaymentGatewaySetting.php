<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'gateway', 'enabled', 'environment',
    'sandbox_client_id', 'sandbox_client_secret', 'sandbox_webhook_id',
    'live_client_id', 'live_client_secret', 'live_webhook_id',
])]
class PaymentGatewaySetting extends Model
{
    protected $hidden = [
        'sandbox_client_id',
        'sandbox_client_secret',
        'sandbox_webhook_id',
        'live_client_id',
        'live_client_secret',
        'live_webhook_id',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'sandbox_client_id' => 'encrypted',
            'sandbox_client_secret' => 'encrypted',
            'sandbox_webhook_id' => 'encrypted',
            'live_client_id' => 'encrypted',
            'live_client_secret' => 'encrypted',
            'live_webhook_id' => 'encrypted',
        ];
    }
}
