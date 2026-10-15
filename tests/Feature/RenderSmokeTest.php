<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseVariant;
use App\Models\Enrolment;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\TrainerAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HTTP-level render smoke tests — catch Blade compile errors, missing
 * relations and wrong column names that unit tests don't reach.
 */
class RenderSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $trainer;

    private User $admin;

    private Course $course;

    private CourseVariant $variant;

    private Enrolment $enrolment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->course = Course::factory()->create();
        $this->variant = CourseVariant::factory()->for($this->course)->create();
        $this->student = User::factory()->student()->create();
        $this->trainer = User::factory()->trainer()->create();
        $this->admin = User::factory()->superAdmin()->create();
        $this->enrolment = Enrolment::factory()->create([
            'user_id' => $this->student->id,
            'course_variant_id' => $this->variant->id,
        ]);
        TrainerAssignment::create(['trainer_id' => $this->trainer->id, 'course_id' => $this->course->id]);
    }

    // ---------- student surfaces ----------

    public function test_student_dashboard_renders(): void
    {
        $this->actingAs($this->student)->get(route('student.dashboard'))->assertOk();
    }

    public function test_student_course_show_renders(): void
    {
        $this->actingAs($this->student)->get(route('learn.course', $this->enrolment))->assertOk();
    }

    public function test_student_lesson_show_renders(): void
    {
        $module = CourseModule::create(['course_id' => $this->course->id, 'title' => 'M', 'status' => 'published']);
        $lesson = Lesson::create(['course_module_id' => $module->id, 'title' => 'L', 'status' => 'published', 'content' => 'x']);

        $this->actingAs($this->student)
            ->get(route('learn.lesson', [$this->enrolment, $lesson]))->assertOk();
    }

    public function test_student_assignment_show_renders(): void
    {
        $assignment = Assignment::factory()->for($this->course)->create(['status' => 'published', 'audience' => 'course']);

        $this->actingAs($this->student)
            ->get(route('learn.assignment', [$this->enrolment, $assignment]))->assertOk();
    }

    public function test_student_quiz_take_renders(): void
    {
        $quiz = Quiz::factory()->for($this->course)->create(['status' => 'published']);

        $this->actingAs($this->student)
            ->get(route('learn.quiz', [$this->enrolment, $quiz]))->assertOk();
    }

    public function test_student_results_and_training_render(): void
    {
        $this->actingAs($this->student)->get(route('student.results'))->assertOk();
        $this->actingAs($this->student)->get(route('student.training'))->assertOk();
    }

    // ---------- trainer surfaces ----------

    public function test_trainer_dashboard_renders(): void
    {
        $this->actingAs($this->trainer)->get(route('trainer.dashboard'))->assertOk();
    }

    public function test_trainer_manage_surfaces_render(): void
    {
        foreach (['course.manage', 'sessions', 'groups', 'assignments', 'quizzes', 'students'] as $name) {
            $this->actingAs($this->trainer)
                ->get(route('trainer.'.$name, $this->course))
                ->assertOk();
        }
    }

    public function test_trainer_grading_and_quiz_builder_render(): void
    {
        $assignment = Assignment::factory()->for($this->course)->create();
        $quiz = Quiz::factory()->for($this->course)->create();

        $this->actingAs($this->trainer)->get(route('trainer.grading', $assignment))->assertOk();
        $this->actingAs($this->trainer)->get(route('trainer.quiz.builder', $quiz))->assertOk();
        $this->actingAs($this->trainer)->get(route('trainer.quiz.attempts', $quiz))->assertOk();
    }

    public function test_trainer_availability_renders(): void
    {
        $this->actingAs($this->trainer)->get(route('trainer.availability'))->assertOk();
    }

    // ---------- admin surfaces ----------

    public function test_admin_surfaces_render(): void
    {
        foreach (['dashboard', 'programmes', 'courses', 'users', 'orders', 'enrolments', 'integrations', 'audit-logs'] as $page) {
            $this->actingAs($this->admin)
                ->get(route('admin.'.str_replace('-', '.', $page) === 'dashboard' ? 'admin.dashboard' : 'admin.'.$page))
                ->assertOk();
        }
    }

    // ---------- role boundaries ----------

    public function test_student_cannot_reach_trainer_area(): void
    {
        $this->actingAs($this->student)->get(route('trainer.dashboard'))->assertForbidden();
    }

    public function test_trainer_cannot_reach_admin_area(): void
    {
        $this->actingAs($this->trainer)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_guest_redirected_to_login(): void
    {
        $this->get(route('student.dashboard'))->assertRedirect(route('login'));
        $this->get(route('trainer.dashboard'))->assertRedirect(route('login'));
    }
}
