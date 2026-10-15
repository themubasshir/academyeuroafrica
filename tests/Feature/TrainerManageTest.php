<?php

namespace Tests\Feature;

use App\Livewire\Trainer\Availability;
use App\Livewire\Trainer\CourseManage;
use App\Livewire\Trainer\QuizBuilder;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\TrainerAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TrainerManageTest extends TestCase
{
    use RefreshDatabase;

    private function assignedTrainer(Course $course): User
    {
        $trainer = User::factory()->trainer()->create();
        TrainerAssignment::create(['trainer_id' => $trainer->id, 'course_id' => $course->id]);

        return $trainer;
    }

    public function test_assigned_trainer_creates_module_and_lesson(): void
    {
        $course = Course::factory()->create();
        $trainer = $this->assignedTrainer($course);

        Livewire::actingAs($trainer)
            ->test(CourseManage::class, ['course' => $course])
            ->set('moduleTitle', 'Module 1')
            ->call('saveModule')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('course_modules', ['course_id' => $course->id, 'title' => 'Module 1']);
    }

    public function test_unassigned_trainer_blocked_from_manage(): void
    {
        $course = Course::factory()->create();
        $trainer = User::factory()->trainer()->create();

        $this->actingAs($trainer)->get(route('trainer.course.manage', $course))->assertForbidden();
    }

    public function test_trainer_creates_quiz_and_question(): void
    {
        $course = Course::factory()->create();
        $trainer = $this->assignedTrainer($course);
        $quiz = Quiz::factory()->for($course)->create(['status' => 'draft']);

        Livewire::actingAs($trainer)
            ->test(QuizBuilder::class, ['quiz' => $quiz])
            ->set('qPrompt', '2+2?')
            ->set('qType', 'mcq')
            ->set('qOptions', [['label' => '4', 'correct' => true], ['label' => '5', 'correct' => false]])
            ->call('addQuestion')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('quiz_questions', ['quiz_id' => $quiz->id, 'prompt' => '2+2?']);
        $this->assertDatabaseHas('quiz_options', ['label' => '4', 'is_correct' => true]);
    }

    public function test_trainer_adds_availability(): void
    {
        $trainer = User::factory()->trainer()->create();

        Livewire::actingAs($trainer)
            ->test(Availability::class)
            ->set('date', now()->addWeek()->format('Y-m-d'))
            ->set('startTime', '09:00')
            ->set('endTime', '12:00')
            ->call('add')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('trainer_availability', ['trainer_id' => $trainer->id]);
    }
}
