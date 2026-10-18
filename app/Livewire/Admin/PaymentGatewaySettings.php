<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\PaymentGatewaySetting;
use App\Services\Payments\Gateways\PayPalGateway;
use App\Services\Payments\PayPalConfiguration;
use Livewire\Component;
use Throwable;

class PaymentGatewaySettings extends Component
{
    public bool $enabled = false;

    public string $activeEnvironment = 'sandbox';

    public array $credentials = [
        'sandbox' => ['client_id' => '', 'client_secret' => '', 'webhook_id' => ''],
        'live' => ['client_id' => '', 'client_secret' => '', 'webhook_id' => ''],
    ];

    public array $configurationStatus = [];

    public array $connectionStatus = [];

    public ?string $statusMessage = null;

    public function mount(PayPalConfiguration $configuration): void
    {
        $this->authorizeAdministrator();
        $this->enabled = $configuration->isEnabled();
        $this->activeEnvironment = in_array($configuration->environment(), ['sandbox', 'live'], true)
            ? $configuration->environment()
            : 'sandbox';
        $this->refreshConfigurationStatus($configuration);
    }

    public function saveCredentials(string $environment, PayPalConfiguration $configuration): void
    {
        $this->authorizeAdministrator();
        $this->assertEnvironment($environment);

        $data = $this->validate([
            "credentials.{$environment}.client_id" => ['nullable', 'string', 'max:2048'],
            "credentials.{$environment}.client_secret" => ['nullable', 'string', 'max:2048'],
            "credentials.{$environment}.webhook_id" => ['nullable', 'string', 'max:2048'],
        ])['credentials'][$environment];

        $updates = [];
        foreach (['client_id', 'client_secret', 'webhook_id'] as $field) {
            if (filled($data[$field])) {
                $updates["{$environment}_{$field}"] = trim($data[$field]);
            }
        }

        if ($updates === []) {
            $this->statusMessage = 'No credential changes were submitted.';

            return;
        }

        $setting = PaymentGatewaySetting::firstOrCreate(
            ['gateway' => 'paypal'],
            [
                'enabled' => false,
                'environment' => $this->activeEnvironment,
            ]
        );
        $setting->update($updates);

        AuditLog::record('payment_gateway.credentials_updated', $setting, [
            'gateway' => 'paypal',
            'environment' => $environment,
            'fields' => array_map(
                static fn (string $field): string => str_replace("{$environment}_", '', $field),
                array_keys($updates)
            ),
        ]);

        $this->credentials[$environment] = ['client_id' => '', 'client_secret' => '', 'webhook_id' => ''];
        $this->connectionStatus[$environment] = null;
        $this->statusMessage = ucfirst($environment).' credentials saved securely.';
        $updatedConfiguration = new PayPalConfiguration;
        $this->enabled = $updatedConfiguration->isEnabled();
        $this->refreshConfigurationStatus($updatedConfiguration);
    }

    public function saveGatewayState(PayPalConfiguration $configuration): void
    {
        $this->authorizeAdministrator();
        $data = $this->validate([
            'enabled' => ['required', 'boolean'],
            'activeEnvironment' => ['required', 'in:sandbox,live'],
        ]);

        if ($data['enabled']) {
            abort_unless(
                $configuration->isConfigured($data['activeEnvironment']),
                422,
                'Complete the selected environment credentials and webhook ID before enabling PayPal.'
            );
        }

        $setting = PaymentGatewaySetting::firstOrCreate(
            ['gateway' => 'paypal'],
            ['enabled' => false, 'environment' => 'sandbox']
        );
        $previous = ['enabled' => $setting->enabled, 'environment' => $setting->environment];
        $setting->update([
            'enabled' => $data['enabled'],
            'environment' => $data['activeEnvironment'],
        ]);

        AuditLog::record('payment_gateway.state_updated', $setting, [
            'gateway' => 'paypal',
            'previous' => $previous,
            'current' => ['enabled' => $setting->enabled, 'environment' => $setting->environment],
        ]);

        $this->statusMessage = 'PayPal gateway state updated.';
        $this->refreshConfigurationStatus(new PayPalConfiguration);
    }

    public function testConnection(string $environment): void
    {
        $this->authorizeAdministrator();
        $this->assertEnvironment($environment);

        try {
            (new PayPalGateway($environment))->testConnection();
            $this->connectionStatus[$environment] = 'success';
            $this->statusMessage = ucfirst($environment).' connection succeeded.';
        } catch (Throwable $exception) {
            report($exception);
            $this->connectionStatus[$environment] = 'failed';
            $this->statusMessage = ucfirst($environment).' connection failed. Check the saved credentials and try again.';
        }

        AuditLog::record('payment_gateway.connection_tested', null, [
            'gateway' => 'paypal',
            'environment' => $environment,
            'result' => $this->connectionStatus[$environment],
        ]);
    }

    public function render()
    {
        return view('livewire.admin.payment-gateway-settings')
            ->layout('layouts::app', ['title' => 'Payment Gateway']);
    }

    private function refreshConfigurationStatus(PayPalConfiguration $configuration): void
    {
        $this->configurationStatus = [
            'sandbox' => $configuration->status('sandbox'),
            'live' => $configuration->status('live'),
        ];
    }

    private function authorizeAdministrator(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    private function assertEnvironment(string $environment): void
    {
        abort_unless(in_array($environment, ['sandbox', 'live'], true), 422);
    }
}
