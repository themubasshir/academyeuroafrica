<?php

namespace Tests\Feature\Academic;

use App\Models\Enrolment;
use App\Models\TrainerAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrolmentPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_view_own_enrolment(): void
    {
        $enrolment = Enrolment::factory()->create();

        $this->assertTrue($enrolment->user->can('view', $enrolment));
    }

    public function test_student_cannot_view_another_students_enrolment(): void
    {
        $enrolment = Enrolment::factory()->create();
        $other = User::factory()->student()->create();

        $this->assertFalse($other->can('view', $enrolment));
    }

    public function test_assigned_trainer_can_view_enrolment_on_their_course(): void
    {
        $enrolment = Enrolment::factory()->create();
        $trainer = User::factory()->trainer()->create();
        TrainerAssignment::create([
            'trainer_id' => $trainer->id,
            'course_id' => $enrolment->variant->course_id,
        ]);

        $this->assertTrue($trainer->can('view', $enrolment));
    }

    public function test_unassigned_trainer_cannot_view_enrolment(): void
    {
        $enrolment = Enrolment::factory()->create();
        $trainer = User::factory()->trainer()->create();

        $this->assertFalse($trainer->can('view', $enrolment));
    }

    public function test_admin_can_view_any_enrolment(): void
    {
        $enrolment = Enrolment::factory()->create();
        $admin = User::factory()->academyAdmin()->create();

        $this->assertTrue($admin->can('view', $enrolment));
    }
}
