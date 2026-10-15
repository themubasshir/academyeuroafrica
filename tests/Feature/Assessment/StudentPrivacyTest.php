<?php

namespace Tests\Feature\Assessment;

use App\Models\AssignmentSubmission;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cross-student IDOR checks — a student must never read another's work.
 */
class StudentPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_cannot_view_anothers_submission(): void
    {
        $submission = AssignmentSubmission::factory()->create();
        $other = User::factory()->student()->create();

        $this->assertFalse($other->can('view', $submission));
    }

    public function test_student_can_view_own_submission(): void
    {
        $submission = AssignmentSubmission::factory()->create();

        $this->assertTrue($submission->user->can('view', $submission));
    }

    public function test_student_cannot_view_anothers_quiz_attempt(): void
    {
        $attempt = QuizAttempt::factory()->create();
        $other = User::factory()->student()->create();

        $this->assertFalse($other->can('view', $attempt));
    }

    public function test_student_cannot_grade_submission(): void
    {
        $submission = AssignmentSubmission::factory()->create();

        $this->assertFalse($submission->user->can('grade', $submission));
    }
}
