<?php

namespace Tests\Feature;

use App\Livewire\Trainer\Availability;
use App\Livewire\Trainer\CourseManage;
use App\Livewire\Trainer\QuizBuilder;
use App\Models\Course;
use App\Models\CourseVariant;
use App\Models\Enrolment;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\TrainerAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_trainer_uploads_material_to_course(): void
    {
        Storage::fake('local');
        $course = Course::factory()->create();
        $trainer = $this->assignedTrainer($course);

        Livewire::actingAs($trainer)
            ->test(CourseManage::class, ['course' => $course])
            ->set('materialUpload', UploadedFile::fake()->create('handbook.pdf', 100, 'application/pdf'))
            ->set('materialDescription', 'Course handbook')
            ->set('materialAudience', 'course')
            ->call('uploadMaterial')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('materials', [
            'attachable_type' => Course::class,
            'attachable_id' => $course->id,
            'original_name' => 'handbook.pdf',
        ]);
        $this->assertCount(1, Storage::disk('local')->allFiles('materials'));
    }

    public function test_trainer_uploads_student_targeted_material(): void
    {
        Storage::fake('local');
        $course = Course::factory()->create();
        $trainer = $this->assignedTrainer($course);
        $student = User::factory()->student()->create();
        $variant = CourseVariant::factory()->for($course)->create();
        Enrolment::firstOrCreate(['user_id' => $student->id, 'course_variant_id' => $variant->id], ['status' => 'active', 'enrolled_at' => now()]);

        Livewire::actingAs($trainer)
            ->test(CourseManage::class, ['course' => $course])
            ->set('materialUpload', UploadedFile::fake()->create('feedback.pdf', 50, 'application/pdf'))
            ->set('materialAudience', 'student')
            ->set('materialStudentId', $student->id)
            ->call('uploadMaterial')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('materials', ['student_id' => $student->id, 'original_name' => 'feedback.pdf']);
    }

    public function test_group_audience_requires_group_id(): void
    {
        Storage::fake('local');
        $course = Course::factory()->create();
        $trainer = $this->assignedTrainer($course);

        Livewire::actingAs($trainer)
            ->test(CourseManage::class, ['course' => $course])
            ->set('materialUpload', UploadedFile::fake()->create('x.pdf', 50, 'application/pdf'))
            ->set('materialAudience', 'group')
            ->call('uploadMaterial')
            ->assertHasErrors(['materialGroupId']);
    }

    public function test_trainer_deletes_material(): void
    {
        Storage::fake('local');
        $course = Course::factory()->create();
        $trainer = $this->assignedTrainer($course);
        Storage::disk('local')->put('materials/d.pdf', 'x');
        $material = Material::create([
            'uploader_id' => $trainer->id,
            'attachable_type' => Course::class, 'attachable_id' => $course->id,
            'disk' => 'local', 'path' => 'materials/d.pdf', 'original_name' => 'd.pdf',
            'mime' => 'application/pdf', 'size_bytes' => 10, 'visibility' => 'enrolled',
        ]);

        Livewire::actingAs($trainer)
            ->test(CourseManage::class, ['course' => $course])
            ->call('deleteMaterial', $material->id);

        $this->assertDatabaseMissing('materials', ['id' => $material->id]);
        $this->assertFalse(Storage::disk('local')->exists('materials/d.pdf'));
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
