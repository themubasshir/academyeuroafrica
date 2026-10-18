<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_links_to_password_reset_request(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Forgot Password?')
            ->assertSee(route('password.request'));
    }

    public function test_password_reset_request_uses_generic_response_for_unknown_email(): void
    {
        NotificationFacade::fake();

        $response = $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'unknown@example.test']);

        $response->assertRedirect(route('password.request'))
            ->assertSessionHas('status', 'If an account matches that email, a reset link is on its way.');
        NotificationFacade::assertNothingSent();
    }

    public function test_password_reset_request_sends_notification_for_existing_email(): void
    {
        NotificationFacade::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', 'If an account matches that email, a reset link is on its way.');

        NotificationFacade::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_reset_request_is_rate_limited(): void
    {
        NotificationFacade::fake();
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('password.email'), ['email' => $user->email]);
        }

        $this->post(route('password.email'), ['email' => $user->email])->assertStatus(429);
    }

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

    public function test_password_reset_token_is_one_time_use(): void
    {
        NotificationFacade::fake();
        $user = User::factory()->create(['password' => 'old-password']);
        $token = Password::broker()->createToken($user);

        $payload = [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ];

        $this->post(route('password.store'), $payload)
            ->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));

        $this->post(route('password.store'), $payload)
            ->assertRedirect()
            ->assertSessionHasErrors('email');
    }

    public function test_password_reset_token_expires(): void
    {
        NotificationFacade::fake();
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);
        DB::table('password_reset_tokens')->where('email', $user->email)->update([
            'created_at' => now()->subMinutes(61),
        ]);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertRedirect()->assertSessionHasErrors('email');
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
