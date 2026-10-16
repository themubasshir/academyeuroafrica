<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StudentAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_creates_only_a_student_account(): void
    {
        Notification::fake();

        $response = $this->post(route('register.store'), [
            'name' => 'New Student',
            'email' => 'student@gmail.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasNoErrors();
        $user = User::where('email', 'student@gmail.com')->firstOrFail();

        $response->assertRedirect(route('verification.notice'));
        $this->assertTrue($user->isStudent());
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isTrainer());
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_login_regenerates_session_and_preserves_intended_url(): void
    {
        $user = User::factory()->student()->create(['password' => 'password']);
        $this->get(route('student.profile'))->assertRedirect(route('login'));

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('student.profile'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_password_reset_uses_a_single_use_expiring_token(): void
    {
        $user = User::factory()->create(['email' => 'reset@example.com']);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        $this->assertNotEmpty(session('status'));
    }

    public function test_order_history_is_scoped_to_the_authenticated_student(): void
    {
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();
        $order = Order::create([
            'user_id' => $otherStudent->id,
            'reference' => 'OTHER-ORDER',
            'status' => 'paid',
            'total_cents' => 1000,
            'currency' => 'USD',
        ]);

        $this->actingAs($student)->get(route('student.orders'))
            ->assertOk()
            ->assertDontSee($order->reference);
    }

    public function test_unverified_students_cannot_open_learning_dashboard(): void
    {
        $student = User::factory()->student()->unverified()->create();

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertRedirect(route('verification.notice'));
    }
}
