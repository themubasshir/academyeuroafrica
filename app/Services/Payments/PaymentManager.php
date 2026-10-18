<?php

namespace App\Services\Payments;

use App\Services\Payments\Contracts\PaymentGateway;
use App\Services\Payments\Gateways\PayPalGateway;
use App\Services\Payments\Gateways\TestGateway;
use RuntimeException;

class PaymentManager
{
    /** @return array<string, PaymentGateway> */
    public function gateways(): array
    {
        $gateways = ['paypal' => new PayPalGateway];

        if (app()->environment(['local', 'testing'])) {
            $gateways['test'] = new TestGateway;
        }

        return $gateways;
    }

    public function driver(?string $name = null, bool $requireEnabled = true): PaymentGateway
    {
        $name ??= config('services.payments.default', 'paypal');
        $gateway = $this->gateways()[$name]
            ?? throw new RuntimeException("Unknown payment gateway: {$name}");

        if ($requireEnabled && $gateway instanceof PayPalGateway && ! $gateway->isEnabled()) {
            throw new RuntimeException('PayPal is disabled.');
        }

        if ($gateway instanceof PayPalGateway && ! $gateway->isConfigured()) {
            throw new RuntimeException('PayPal is not completely configured.');
        }

        return $gateway;
    }
}
