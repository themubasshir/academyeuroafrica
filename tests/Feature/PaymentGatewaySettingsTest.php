<?php

namespace Tests\Feature;

use App\Livewire\Admin\PaymentGatewaySettings;
use App\Models\AuditLog;
use App\Models\PaymentGatewaySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentGatewaySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_can_open_payment_gateway_settings(): void
    {
        $student = User::factory()->student()->create();
        $admin = User::factory()->academyAdmin()->create();

        $this->actingAs($student)
            ->get(route('admin.settings.payment-gateway'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.settings.payment-gateway'))
            ->assertOk()
            ->assertSee('Payment Gateway');
    }

    public function test_sandbox_credentials_are_encrypted_and_never_returned_to_component(): void
    {
        $admin = User::factory()->academyAdmin()->create();

        Livewire::actingAs($admin)
            ->test(PaymentGatewaySettings::class)
            ->set('credentials.sandbox.client_id', 'sandbox-client-id')
            ->set('credentials.sandbox.client_secret', 'sandbox-client-secret')
            ->set('credentials.sandbox.webhook_id', 'sandbox-webhook-id')
            ->call('saveCredentials', 'sandbox')
            ->assertHasNoErrors()
            ->assertSet('credentials.sandbox.client_id', '')
            ->assertSet('credentials.sandbox.client_secret', '')
            ->assertSet('credentials.sandbox.webhook_id', '')
            ->assertDontSee('sandbox-client-secret');

        $setting = PaymentGatewaySetting::where('gateway', 'paypal')->firstOrFail();
        $this->assertSame('sandbox-client-id', $setting->sandbox_client_id);
        $this->assertSame('sandbox-client-secret', $setting->sandbox_client_secret);
        $this->assertSame('sandbox-webhook-id', $setting->sandbox_webhook_id);
        $this->assertArrayNotHasKey('sandbox_client_secret', $setting->toArray());
        $this->assertNotSame('sandbox-client-secret', DB::table('payment_gateway_settings')->value('sandbox_client_secret'));
        $this->assertStringNotContainsString('sandbox-client-secret', json_encode(AuditLog::latest('id')->value('payload')));
    }

    public function test_blank_fields_do_not_overwrite_saved_credentials(): void
    {
        $admin = User::factory()->academyAdmin()->create();
        PaymentGatewaySetting::create([
            'gateway' => 'paypal',
            'enabled' => false,
            'environment' => 'sandbox',
            'sandbox_client_id' => 'saved-client',
            'sandbox_client_secret' => 'saved-secret',
            'sandbox_webhook_id' => 'saved-webhook',
        ]);

        Livewire::actingAs($admin)
            ->test(PaymentGatewaySettings::class)
            ->call('saveCredentials', 'sandbox')
            ->assertHasNoErrors();

        $setting = PaymentGatewaySetting::where('gateway', 'paypal')->firstOrFail();
        $this->assertSame('saved-client', $setting->sandbox_client_id);
        $this->assertSame('saved-secret', $setting->sandbox_client_secret);
        $this->assertSame('saved-webhook', $setting->sandbox_webhook_id);
    }

    public function test_live_credentials_are_isolated_and_do_not_change_active_environment(): void
    {
        $admin = User::factory()->academyAdmin()->create();
        $setting = PaymentGatewaySetting::create([
            'gateway' => 'paypal',
            'enabled' => false,
            'environment' => 'sandbox',
            'sandbox_client_id' => 'sandbox-client',
            'sandbox_client_secret' => 'sandbox-secret',
            'sandbox_webhook_id' => 'sandbox-webhook',
        ]);

        Livewire::actingAs($admin)
            ->test(PaymentGatewaySettings::class)
            ->set('credentials.live.client_id', 'live-client')
            ->set('credentials.live.client_secret', 'live-secret')
            ->set('credentials.live.webhook_id', 'live-webhook')
            ->call('saveCredentials', 'live')
            ->assertHasNoErrors();

        $setting->refresh();
        $this->assertSame('sandbox', $setting->environment);
        $this->assertSame('sandbox-secret', $setting->sandbox_client_secret);
        $this->assertSame('live-secret', $setting->live_client_secret);
    }

    public function test_gateway_cannot_be_enabled_without_webhook_id(): void
    {
        $admin = User::factory()->academyAdmin()->create();
        PaymentGatewaySetting::create([
            'gateway' => 'paypal',
            'enabled' => false,
            'environment' => 'sandbox',
            'sandbox_client_id' => 'sandbox-client',
            'sandbox_client_secret' => 'sandbox-secret',
        ]);

        Livewire::actingAs($admin)
            ->test(PaymentGatewaySettings::class)
            ->set('enabled', true)
            ->set('activeEnvironment', 'sandbox')
            ->call('saveGatewayState')
            ->assertStatus(422);

        $this->assertFalse(PaymentGatewaySetting::firstOrFail()->enabled);
    }

    public function test_safe_connection_test_reports_invalid_credentials_without_exposing_them(): void
    {
        $admin = User::factory()->academyAdmin()->create();
        PaymentGatewaySetting::create([
            'gateway' => 'paypal',
            'enabled' => false,
            'environment' => 'sandbox',
            'sandbox_client_id' => 'invalid-client',
            'sandbox_client_secret' => 'invalid-secret',
            'sandbox_webhook_id' => 'sandbox-webhook',
        ]);
        Http::fake(['*/v1/oauth2/token' => Http::response(['error' => 'invalid_client'], 401)]);

        Livewire::actingAs($admin)
            ->test(PaymentGatewaySettings::class)
            ->call('testConnection', 'sandbox')
            ->assertSet('connectionStatus.sandbox', 'failed')
            ->assertSee('Credentials were not exposed.')
            ->assertDontSee('invalid-secret');

        $this->assertStringNotContainsString('invalid-secret', json_encode(AuditLog::latest('id')->value('payload')));
    }

    public function test_sandbox_connection_succeeds_and_live_activation_is_explicit(): void
    {
        $admin = User::factory()->academyAdmin()->create();
        PaymentGatewaySetting::create([
            'gateway' => 'paypal',
            'enabled' => false,
            'environment' => 'sandbox',
            'sandbox_client_id' => 'sandbox-client',
            'sandbox_client_secret' => 'sandbox-secret',
            'sandbox_webhook_id' => 'sandbox-webhook',
            'live_client_id' => 'live-client',
            'live_client_secret' => 'live-secret',
            'live_webhook_id' => 'live-webhook',
        ]);
        Http::fake(['*/v1/oauth2/token' => Http::response(['access_token' => 'sandbox-token'])]);

        Livewire::actingAs($admin)
            ->test(PaymentGatewaySettings::class)
            ->call('testConnection', 'sandbox')
            ->assertSet('connectionStatus.sandbox', 'success')
            ->set('enabled', true)
            ->set('activeEnvironment', 'live')
            ->call('saveGatewayState')
            ->assertHasNoErrors();

        $setting = PaymentGatewaySetting::firstOrFail();
        $this->assertTrue($setting->enabled);
        $this->assertSame('live', $setting->environment);
    }
}
