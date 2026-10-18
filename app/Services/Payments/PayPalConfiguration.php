<?php

namespace App\Services\Payments;

use App\Models\PaymentGatewaySetting;

class PayPalConfiguration
{
    public function environment(): string
    {
        return $this->setting()?->environment
            ?? (string) config('services.paypal.mode', 'sandbox');
    }

    public function isEnabled(): bool
    {
        $setting = $this->setting();

        return $setting !== null
            ? $setting->enabled
            : (bool) config('services.paypal.enabled', true);
    }

    /** @return array{client_id:?string, client_secret:?string, webhook_id:?string} */
    public function credentials(?string $environment = null): array
    {
        $environment ??= $this->environment();

        if (! in_array($environment, ['sandbox', 'live'], true)) {
            return ['client_id' => null, 'client_secret' => null, 'webhook_id' => null];
        }

        $setting = $this->setting();

        return [
            'client_id' => $setting?->getAttribute("{$environment}_client_id")
                ?: $this->environmentValue($environment, 'client_id'),
            'client_secret' => $setting?->getAttribute("{$environment}_client_secret")
                ?: $this->environmentValue($environment, 'client_secret'),
            'webhook_id' => $setting?->getAttribute("{$environment}_webhook_id")
                ?: $this->environmentValue($environment, 'webhook_id'),
        ];
    }

    public function isConfigured(?string $environment = null): bool
    {
        $environment ??= $this->environment();
        $credentials = $this->credentials($environment);

        return in_array($environment, ['sandbox', 'live'], true)
            && filled($credentials['client_id'])
            && filled($credentials['client_secret'])
            && filled($credentials['webhook_id']);
    }

    /** @return array{client_id:bool, client_secret:bool, webhook_id:bool, complete:bool} */
    public function status(string $environment): array
    {
        $credentials = $this->credentials($environment);

        return [
            'client_id' => filled($credentials['client_id']),
            'client_secret' => filled($credentials['client_secret']),
            'webhook_id' => filled($credentials['webhook_id']),
            'complete' => $this->isConfigured($environment),
        ];
    }

    private function setting(): ?PaymentGatewaySetting
    {
        return PaymentGatewaySetting::where('gateway', 'paypal')->first();
    }

    private function environmentValue(string $environment, string $key): ?string
    {
        $specific = config("services.paypal.{$environment}.{$key}");
        if (filled($specific)) {
            return (string) $specific;
        }

        if (config('services.paypal.mode') === $environment) {
            $legacy = config("services.paypal.{$key}");

            return filled($legacy) ? (string) $legacy : null;
        }

        return null;
    }
}
