<?php

namespace Tests\Feature;

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

    public function test_denies_disallowed_command(): void
    {
        Storage::disk('local')->put('deploy.token', 'right-token');

        $this->hit(['token' => 'right-token', 'run' => 'tinker'])->assertStatus(422);
    }

    public function test_denies_after_done_flag(): void
    {
        Storage::disk('local')->put('deploy.token', 'right-token');
        Storage::disk('local')->put('deploy.done', 'done');

        $this->hit(['token' => 'right-token'])->assertNotFound();
    }
}
