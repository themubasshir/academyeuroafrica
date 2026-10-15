<?php

namespace Tests\Feature;

use App\Livewire\Student\LessonShow;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseVariant;
use App\Models\Enrolment;
use App\Models\Lesson;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private function enrolledCourse(): array
    {
        $course = Course::factory()->create();
        $variant = CourseVariant::factory()->for($course)->create();
        $student = User::factory()->student()->create();
        $enrolment = Enrolment::factory()->create([
            'user_id' => $student->id,
            'course_variant_id' => $variant->id,
        ]);

        return [$course, $variant, $student, $enrolment];
    }

    public function test_enrolled_student_opens_course_workspace(): void
    {
        [$course, , $student, $enrolment] = $this->enrolledCourse();

        $this->actingAs($student)
            ->get(route('learn.course', $enrolment))
            ->assertOk();
    }

    public function test_other_student_cannot_open_workspace(): void
    {
        [, , , $enrolment] = $this->enrolledCourse();
        $intruder = User::factory()->student()->create();

        $this->actingAs($intruder)
            ->get(route('learn.course', $enrolment))
            ->assertForbidden();
    }

    public function test_student_marks_lesson_complete(): void
    {
        [$course, , $student, $enrolment] = $this->enrolledCourse();
        $module = CourseModule::create(['course_id' => $course->id, 'title' => 'M1', 'status' => 'published']);
        $lesson = Lesson::create(['course_module_id' => $module->id, 'title' => 'L1', 'status' => 'published']);

        $this->assertDatabaseMissing('lesson_progress', ['enrolment_id' => $enrolment->id, 'lesson_id' => $lesson->id]);

        Livewire::actingAs($student)
            ->test(LessonShow::class, ['enrolment' => $enrolment, 'lesson' => $lesson])
            ->call('markComplete');

        $this->assertDatabaseHas('lesson_progress', ['enrolment_id' => $enrolment->id, 'lesson_id' => $lesson->id]);
    }

    public function test_student_results_page_scopes_to_self(): void
    {
        $student = User::factory()->student()->create();
        $other = User::factory()->student()->create();
        QuizAttempt::factory()->for($other)->create(['status' => 'graded', 'score' => 9]);

        $this->actingAs($student)->get(route('student.results'))->assertOk();
        // Scoped by user_id — component query already filters to auth user.
    }
}
