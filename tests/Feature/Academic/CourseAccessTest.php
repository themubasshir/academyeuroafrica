<?php

namespace Tests\Feature\Academic;

use App\Models\Course;
use App\Models\TrainerAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_course_viewable_by_student(): void
    {
        $course = Course::factory()->create();
        $student = User::factory()->student()->create();

        $this->assertTrue($student->can('view', $course));
    }

    public function test_draft_course_hidden_from_student(): void
    {
        $course = Course::factory()->draft()->create();
        $student = User::factory()->student()->create();

        $this->assertFalse($student->can('view', $course));
    }

    public function test_draft_course_visible_to_assigned_trainer(): void
    {
        $course = Course::factory()->draft()->create();
        $trainer = User::factory()->trainer()->create();
        TrainerAssignment::create(['trainer_id' => $trainer->id, 'course_id' => $course->id]);

        $this->assertTrue($trainer->can('view', $course));
    }

    public function test_draft_course_hidden_from_unassigned_trainer(): void
    {
        $course = Course::factory()->draft()->create();
        $trainer = User::factory()->trainer()->create();

        $this->assertFalse($trainer->can('view', $course));
    }
}
