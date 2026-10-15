<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_log_in_and_is_redirected_to_student_dashboard(): void
    {
        $user = User::factory()->student()->create(['password' => 'secret-password']);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('student.dashboard'));
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_disabled_account_is_logged_out_and_denied(): void
    {
        $user = User::factory()->create([
            'password' => 'secret-password',
            'status' => 'disabled',
            'disabled_at' => now(),
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertForbidden();

        $this->assertGuest();
    }

    public function test_inactive_user_is_kicked_out_of_protected_pages(): void
    {
        $user = User::factory()->student()->create(['status' => 'disabled']);

        $this->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertForbidden();

        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'bad']);
        }

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'secret-password'])
            ->assertStatus(429);
    }
}
