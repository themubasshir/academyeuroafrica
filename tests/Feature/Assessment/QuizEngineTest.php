<?php

namespace Tests\Feature\Assessment;

use App\Domain\Assessment\QuizService;
use App\Models\Enrolment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizEngineTest extends TestCase
{
    use RefreshDatabase;

    private QuizService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QuizService;
    }

    private function enrolledStudent(Quiz $quiz): User
    {
        $student = User::factory()->student()->create();
        Enrolment::factory()->create([
            'user_id' => $student->id,
            'course_variant_id' => \App\Models\CourseVariant::factory()->for($quiz->course)->create()->id,
        ]);

        return $student;
    }

    public function test_non_enrolled_cannot_start_attempt(): void
    {
        $quiz = Quiz::factory()->create();
        $student = User::factory()->student()->create();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->service->startAttempt($quiz, $student);
    }

    public function test_attempt_limit_enforced(): void
    {
        $quiz = Quiz::factory()->create(['max_attempts' => 1]);
        $student = $this->enrolledStudent($quiz);

        $attempt = $this->service->startAttempt($quiz, $student);
        $this->service->submit($attempt);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->service->startAttempt($quiz, $student);
    }

    public function test_correct_mcq_answer_scores_points(): void
    {
        $quiz = Quiz::factory()->create();
        $student = $this->enrolledStudent($quiz);
        $q = QuizQuestion::factory()->for($quiz)->create(['type' => 'mcq', 'points' => 5]);
        $correct = QuizOption::create(['quiz_question_id' => $q->id, 'label' => 'Right', 'is_correct' => true]);
        QuizOption::create(['quiz_question_id' => $q->id, 'label' => 'Wrong', 'is_correct' => false]);

        $attempt = $this->service->startAttempt($quiz, $student);
        $this->service->answer($attempt, $q->id, [$correct->id]);
        $attempt = $this->service->submit($attempt);

        $this->assertSame(5, $attempt->score);
        $this->assertSame('graded', $attempt->status);
    }

    public function test_wrong_answer_scores_zero(): void
    {
        $quiz = Quiz::factory()->create();
        $student = $this->enrolledStudent($quiz);
        $q = QuizQuestion::factory()->for($quiz)->create(['type' => 'mcq', 'points' => 5]);
        QuizOption::create(['quiz_question_id' => $q->id, 'label' => 'Right', 'is_correct' => true]);
        $wrong = QuizOption::create(['quiz_question_id' => $q->id, 'label' => 'Wrong', 'is_correct' => false]);

        $attempt = $this->service->startAttempt($quiz, $student);
        $this->service->answer($attempt, $q->id, [$wrong->id]);
        $attempt = $this->service->submit($attempt);

        $this->assertSame(0, $attempt->score);
    }

    public function test_essay_questions_held_for_manual_grading(): void
    {
        $quiz = Quiz::factory()->create();
        $student = $this->enrolledStudent($quiz);
        $q = QuizQuestion::factory()->for($quiz)->create(['type' => 'essay', 'points' => 10]);

        $attempt = $this->service->startAttempt($quiz, $student);
        $this->service->answer($attempt, $q->id, [], 'My essay text');
        $attempt = $this->service->submit($attempt);

        $this->assertSame('submitted', $attempt->status);
        $this->assertNull($attempt->score);
    }

    public function test_expired_attempt_rejects_answers(): void
    {
        $quiz = Quiz::factory()->create(['time_limit_minutes' => 30]);
        $student = $this->enrolledStudent($quiz);
        $attempt = QuizAttempt::factory()->for($quiz)->for($student)->create([
            'started_at' => now()->subHour(),
            'question_order' => [1],
        ]);

        $this->assertTrue($attempt->isExpired());
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->service->answer($attempt, 1, []);
    }

    public function test_cannot_answer_question_outside_attempt(): void
    {
        $quiz = Quiz::factory()->create();
        $student = $this->enrolledStudent($quiz);
        $inQuiz = QuizQuestion::factory()->for($quiz)->create();
        $attempt = $this->service->startAttempt($quiz, $student);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->service->answer($attempt, 999999, []);
    }
}
