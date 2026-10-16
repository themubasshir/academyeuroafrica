<?php

namespace Tests\Feature;

use App\Domain\Assessment\QuizService;
use App\Domain\Certification\CertificateIssuanceService;
use App\Livewire\Student\AssignmentShow;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Certificate;
use App\Models\CertificationPolicy;
use App\Models\Course;
use App\Models\CourseVariant;
use App\Models\Enrolment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LearningIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function enrol(User $student, Course $course, array $attributes = []): Enrolment
    {
        return Enrolment::factory()->create(array_merge([
            'user_id' => $student->id,
            'course_variant_id' => CourseVariant::factory()->for($course)->create()->id,
        ], $attributes));
    }

    public function test_expired_enrolment_cannot_access_learning_or_live_sessions(): void
    {
        $course = Course::factory()->create();
        $student = User::factory()->student()->create();
        $enrolment = $this->enrol($student, $course, ['expires_at' => now()->subSecond()]);

        $this->actingAs($student)->get(route('learn.course', $enrolment))->assertForbidden();
    }

    public function test_expired_enrolment_cannot_start_or_submit_quiz_attempts(): void
    {
        $course = Course::factory()->create();
        $quiz = Quiz::factory()->for($course)->create(['status' => 'published']);
        $student = User::factory()->student()->create();
        $this->enrol($student, $course, ['expires_at' => now()->subSecond()]);

        $this->expectException(HttpException::class);
        app(QuizService::class)->startAttempt($quiz, $student);
    }

    public function test_student_cannot_resubmit_an_assignment_already_awaiting_grading(): void
    {
        $course = Course::factory()->create();
        $student = User::factory()->student()->create();
        $enrolment = $this->enrol($student, $course);
        $assignment = Assignment::factory()->for($course)->create(['status' => 'published']);
        $submission = AssignmentSubmission::factory()->for($assignment)->for($student)->create(['status' => 'submitted']);

        Livewire::actingAs($student)
            ->test(AssignmentShow::class, ['enrolment' => $enrolment, 'assignment' => $assignment])
            ->call('save')
            ->assertStatus(422);
    }

    public function test_inactive_enrolment_cannot_receive_an_automatic_certificate(): void
    {
        Storage::fake('local');
        $course = Course::factory()->create();
        CertificationPolicy::create([
            'course_id' => $course->id,
            'enabled' => true,
            'auto_issue' => true,
            'require_lessons' => false,
            'require_quizzes' => false,
            'require_assignments' => false,
            'min_attendance_percent' => 0,
        ]);
        $student = User::factory()->student()->create();
        $enrolment = $this->enrol($student, $course, ['status' => 'refunded', 'expires_at' => now()]);

        $result = app(CertificateIssuanceService::class)->issueIfEligible($enrolment);

        $this->assertSame('inactive_enrolment', $result['status']);
        $this->assertSame(0, Certificate::count());
    }

    public function test_another_students_quiz_attempt_cannot_be_used_to_submit_answers(): void
    {
        $course = Course::factory()->create();
        $quiz = Quiz::factory()->for($course)->create(['status' => 'published']);
        $question = QuizQuestion::factory()->for($quiz)->create();
        $owner = User::factory()->student()->create();
        $this->enrol($owner, $course);
        $attempt = QuizAttempt::factory()->for($quiz)->for($owner)->create([
            'status' => 'in_progress',
            'question_order' => [$question->id],
        ]);

        $this->actingAs(User::factory()->student()->create());
        $this->expectException(HttpException::class);
        app(QuizService::class)->answer($attempt, $question->id);
    }
}
