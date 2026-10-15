<?php

namespace App\Services\Payments;

use App\Services\Payments\Contracts\PaymentGateway;
use App\Services\Payments\Gateways\StripeGateway;
use App\Services\Payments\Gateways\TestGateway;
use RuntimeException;

class PaymentManager
{
    /** @return array<string, PaymentGateway> */
    public function gateways(): array
    {
        $gateways = ['stripe' => new StripeGateway];

        if (app()->environment(['local', 'testing'])) {
            $gateways['test'] = new TestGateway;
        }

        return $gateways;
    }

    public function driver(?string $name = null): PaymentGateway
    {
        $name ??= config('services.payments.default', 'stripe');
        $gateway = $this->gateways()[$name]
            ?? throw new RuntimeException("Unknown payment gateway: {$name}");

        if (app()->environment('production') && ! $gateway->isLive()) {
            throw new RuntimeException("Gateway {$name} is not permitted in production.");
        }

        return $gateway;
    }
}
