<?php

namespace Tests\Feature;

use App\Livewire\Trainer\CourseManage;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\TrainerAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CurriculumEditorWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_create_edit_and_cancel_workflow(): void
    {
        [$course, $trainer] = $this->managedCourse();

        $component = Livewire::actingAs($trainer)->test(CourseManage::class, ['course' => $course])
            ->call('createModule')
            ->assertSet('showModuleModal', true)
            ->set('moduleTitle', 'Foundations')
            ->set('moduleStatus', 'published')
            ->set('moduleSortOrder', 4)
            ->call('saveModule')
            ->assertHasNoErrors()
            ->assertSet('showModuleModal', false);

        $module = CourseModule::firstOrFail();
        $component->call('editModule', $module->id)
            ->set('moduleTitle', 'Discarded')
            ->call('closeModuleModal');
        $this->assertSame('Foundations', $module->fresh()->title);

        $component->call('editModule', $module->id)
            ->set('moduleTitle', 'Core foundations')
            ->call('saveModule');
        $this->assertDatabaseHas('course_modules', ['id' => $module->id, 'title' => 'Core foundations', 'sort_order' => 4]);
    }

    public function test_pdf_lesson_can_be_created_and_replaced_without_changing_its_material_record(): void
    {
        Storage::fake('local');
        [$course, $trainer, $module] = $this->managedCourse(withModule: true);

        $component = Livewire::actingAs($trainer)->test(CourseManage::class, ['course' => $course])
            ->call('editLesson', null, $module->id)
            ->set('lessonTitle', 'Participant workbook')
            ->set('lessonType', 'pdf')
            ->set('lessonStatus', 'published')
            ->set('requiresAcknowledgement', false)
            ->set('lessonUpload', UploadedFile::fake()->create('workbook.pdf', 100, 'application/pdf'))
            ->call('saveLesson')
            ->assertHasNoErrors()
            ->assertSet('showLessonModal', false);

        $lesson = Lesson::with('primaryMaterial')->firstOrFail();
        $materialId = $lesson->primary_material_id;
        $oldPath = $lesson->primaryMaterial->path;

        $component->call('editLesson', $lesson->id, $module->id)
            ->set('lessonTitle', 'Participant workbook revised')
            ->set('lessonUpload', UploadedFile::fake()->create('workbook-v2.pdf', 120, 'application/pdf'))
            ->call('saveLesson')
            ->assertHasNoErrors();

        $lesson->refresh()->load('primaryMaterial');
        $this->assertSame($materialId, $lesson->primary_material_id);
        $this->assertSame('workbook-v2.pdf', $lesson->primaryMaterial->original_name);
        $this->assertFalse($lesson->requires_acknowledgement);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($lesson->primaryMaterial->path);
    }

    public function test_uploaded_and_hosted_video_lessons_use_source_specific_validation(): void
    {
        Storage::fake('local');
        [$course, $trainer, $module] = $this->managedCourse(withModule: true);

        $component = Livewire::actingAs($trainer)->test(CourseManage::class, ['course' => $course])
            ->call('editLesson', null, $module->id)
            ->set('lessonTitle', 'Private briefing')
            ->set('lessonType', 'video')
            ->set('videoSource', 'upload')
            ->set('videoDurationSeconds', 90)
            ->set('completionThreshold', 80)
            ->call('saveLesson')
            ->assertHasErrors(['lessonUpload']);

        $component->set('lessonUpload', UploadedFile::fake()->create('briefing.webm', 200, 'video/webm'))
            ->call('saveLesson')
            ->assertHasNoErrors();
        $this->assertDatabaseHas('course_videos', ['provider' => 'upload', 'duration_seconds' => 90]);

        $uploadedLesson = Lesson::with('primaryMaterial')->where('title', 'Private briefing')->firstOrFail();
        $uploadedMaterialId = $uploadedLesson->primary_material_id;
        $component->call('editLesson', $uploadedLesson->id, $module->id)
            ->set('lessonTitle', 'Private briefing revised')
            ->set('completionThreshold', 85)
            ->call('saveLesson')
            ->assertHasNoErrors();
        $this->assertSame($uploadedMaterialId, $uploadedLesson->fresh()->primary_material_id);
        $this->assertDatabaseHas('course_videos', ['lesson_id' => $uploadedLesson->id, 'provider' => 'upload', 'duration_seconds' => 90]);
        $this->assertSame(85, $uploadedLesson->fresh()->completion_threshold);

        $component->call('editLesson', null, $module->id)
            ->set('lessonTitle', 'Hosted briefing')
            ->set('lessonType', 'video')
            ->set('videoSource', 'hosted')
            ->set('videoDurationSeconds', 120)
            ->set('videoId', 'https://example.com/not-approved')
            ->call('saveLesson')
            ->assertHasErrors(['videoId'])
            ->set('videoId', 'https://vimeo.com/123456789')
            ->call('saveLesson')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('course_videos', ['provider' => 'generic', 'provider_video_id' => 'https://vimeo.com/123456789']);
    }

    public function test_cancelling_lesson_edit_preserves_existing_content_and_file(): void
    {
        Storage::fake('local');
        [$course, $trainer, $module] = $this->managedCourse(withModule: true);
        $lesson = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Existing lesson',
            'content' => '<p>Original safe content</p>',
            'type' => 'rich_text',
            'status' => 'draft',
        ]);

        Livewire::actingAs($trainer)->test(CourseManage::class, ['course' => $course])
            ->call('editLesson', $lesson->id, $module->id)
            ->set('lessonContent', '<p>Unsaved content</p>')
            ->call('closeLessonModal')
            ->assertSet('showLessonModal', false);

        $this->assertSame('<p>Original safe content</p>', $lesson->fresh()->content);
    }

    /** @return array{Course, User, CourseModule?} */
    private function managedCourse(bool $withModule = false): array
    {
        $course = Course::factory()->create();
        $trainer = User::factory()->trainer()->create();
        TrainerAssignment::create(['trainer_id' => $trainer->id, 'course_id' => $course->id]);
        $module = $withModule ? CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Module one',
            'sort_order' => 1,
            'status' => 'published',
        ]) : null;

        return [$course, $trainer, $module];
    }
}
