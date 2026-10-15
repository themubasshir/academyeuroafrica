<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('student.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_student_cannot_reach_admin_area(): void
    {
        $this->actingAs(User::factory()->student()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_student_cannot_reach_trainer_area(): void
    {
        $this->actingAs(User::factory()->student()->create())
            ->get(route('trainer.dashboard'))
            ->assertForbidden();
    }

    public function test_trainer_can_reach_trainer_but_not_admin(): void
    {
        $trainer = User::factory()->trainer()->create();

        $this->actingAs($trainer)->get(route('trainer.dashboard'))->assertOk();
        $this->actingAs($trainer)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_academy_admin_reaches_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->academyAdmin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_super_admin_reaches_every_area(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('trainer.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('student.dashboard'))->assertOk();
    }
}
