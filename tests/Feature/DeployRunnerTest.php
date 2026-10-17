<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeployRunnerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('app.env', 'local');
        putenv('DEPLOY_ALLOWED_IPS=127.0.0.1');
        $_SERVER['DEPLOY_ALLOWED_IPS'] = '127.0.0.1';
        Storage::fake('local');
    }

    private function hit(array $params = [])
    {
        return $this->postJson(route('deploy.migrate'), $params, ['REMOTE_ADDR' => '127.0.0.1']);
    }

    public function test_denies_without_token_file(): void
    {
        $this->hit(['token' => 'x'])->assertNotFound();
    }

    public function test_denies_wrong_token(): void
    {
        Storage::disk('local')->put('deploy.token', 'right-token');

        $this->hit(['token' => 'wrong'])->assertNotFound();
    }

    public function test_denies_missing_or_expired_expiry(): void
    {
        Storage::disk('local')->put('deploy.token', 'right-token');

        $this->hit(['token' => 'right-token'])->assertNotFound();
        $this->hit(['token' => 'right-token', 'expires_at' => now()->subMinute()->toIso8601String()])->assertNotFound();
    }

    public function test_denies_disallowed_command(): void
    {
        Storage::disk('local')->put('deploy.token', 'right-token');

        $this->hit(['token' => 'right-token', 'expires_at' => now()->addMinute()->toIso8601String(), 'run' => 'tinker'])->assertStatus(422);
    }

    public function test_denies_destructive_migrate_fresh(): void
    {
        Storage::disk('local')->put('deploy.token', 'right-token');

        $this->hit(['token' => 'right-token', 'expires_at' => now()->addMinute()->toIso8601String(), 'run' => 'migrate:fresh --force'])
            ->assertStatus(422);
    }

    public function test_denies_admin_password_rotation_over_http(): void
    {
        Storage::disk('local')->put('deploy.token', 'right-token');

        $this->hit(['token' => 'right-token', 'expires_at' => now()->addMinute()->toIso8601String(), 'run' => 'academy:provision-admin --rotate'])
            ->assertStatus(422);
    }

    public function test_denies_after_done_flag(): void
    {
        Storage::disk('local')->put('deploy.token', 'right-token');
        // Per-command flag: default command is "migrate --force".
        Storage::disk('local')->put('deploy.done.'.md5('migrate --force'), 'done');

        $this->hit(['token' => 'right-token', 'expires_at' => now()->addMinute()->toIso8601String()])->assertNotFound();
    }

    public function test_allows_catalogue_dry_run_in_allowlist(): void
    {
        Storage::disk('local')->put('deploy.token', 'right-token');

        $resp = $this->hit(['token' => 'right-token', 'expires_at' => now()->addMinute()->toIso8601String(), 'run' => 'catalogue:import --dry-run']);
        $this->assertNotEquals(422, $resp->status());
    }

    public function test_allows_catalogue_commands_in_allowlist(): void
    {
        Storage::disk('local')->put('deploy.token', 'right-token');

        $resp = $this->hit(['token' => 'right-token', 'expires_at' => now()->addMinute()->toIso8601String(), 'run' => 'catalogue:variants']);
        $this->assertNotEquals(422, $resp->status());
    }

    public function test_allows_command_list_and_fixed_queue_counts(): void
    {
        Storage::disk('local')->put('deploy.token', 'right-token');

        foreach ([
            'list --raw',
            'db:select --query=select count(*) as jobs from jobs',
            'db:select --query=select count(*) as failed_jobs from failed_jobs',
        ] as $command) {
            $this->assertNotEquals(422, $this->hit(['token' => 'right-token', 'expires_at' => now()->addMinute()->toIso8601String(), 'run' => $command])->status());
        }
    }

    public function test_successful_command_consumes_token_and_cannot_be_replayed(): void
    {
        Storage::disk('local')->put('deploy.token', 'right-token');

        $params = ['token' => 'right-token', 'expires_at' => now()->addMinute()->toIso8601String(), 'run' => 'catalogue:variants'];
        $this->hit($params)->assertSuccessful();
        $this->assertFalse(Storage::disk('local')->exists('deploy.token'));
        $this->hit($params)->assertNotFound();
    }

    public function test_provision_admin_creates_academy_admin(): void
    {
        Storage::disk('local')->put('deploy.token', 'right-token');
        Storage::disk('local')->put('deploy.admin', 'admin@euroafricamedia.eu');

        $this->hit(['token' => 'right-token', 'expires_at' => now()->addMinute()->toIso8601String(), 'run' => 'academy:provision-admin'])
            ->assertSuccessful();

        $user = User::where('email', 'admin@euroafricamedia.eu')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole(Role::ACADEMY_ADMIN));
    }
}
