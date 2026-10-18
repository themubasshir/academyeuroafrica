<?php

namespace Tests\Feature;

use App\Domain\Assessment\QuizService;
use App\Domain\Certification\CertificateIssuanceService;
use App\Domain\Certification\CertificateTrigger;
use App\Domain\Certification\CertificationEligibilityService;
use App\Domain\Files\FileService;
use App\Livewire\Student\AssignmentShow;
use App\Livewire\Student\LessonShow;
use App\Livewire\Trainer\CourseManage;
use App\Livewire\Trainer\Grading;
use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\CertificationPolicy;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseVariant;
use App\Models\Enrolment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\TrainerAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LearningWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_trainer_creates_private_video_lesson_and_playback_threshold_completes_it(): void
    {
        Storage::fake('local');
        [$course, $module, $trainer] = $this->managedCourse();

        Livewire::actingAs($trainer)
            ->test(CourseManage::class, ['course' => $course])
            ->call('editLesson', null, $module->id)
            ->set('lessonTitle', 'Private video')
            ->set('lessonType', 'video')
            ->set('videoSource', 'upload')
            ->set('videoDurationSeconds', 100)
            ->set('completionThreshold', 80)
            ->set('lessonStatus', 'published')
            ->set('lessonUpload', UploadedFile::fake()->create('lesson.mp4', 500, 'video/mp4'))
            ->call('saveLesson')
            ->assertHasNoErrors();

        $lesson = Lesson::with(['video', 'primaryMaterial'])->firstOrFail();
        $this->assertSame('upload', $lesson->video->provider);
        $this->assertSame('video/mp4', $lesson->primaryMaterial->mime);
        Storage::disk('local')->assertExists($lesson->primaryMaterial->path);
        Storage::disk('local')->put($lesson->primaryMaterial->path, str_repeat('v', 100));
        $this->actingAs($trainer)
            ->get(route('trainer.lesson.preview', [$course, $lesson]))
            ->assertOk()
            ->assertSee('Private video');

        [$student, $enrolment] = $this->enrol($course);
        Livewire::actingAs($student)
            ->test(LessonShow::class, compact('enrolment', 'lesson'))
            ->call('savePosition', 79)
            ->assertSet('completed', false)
            ->call('savePosition', 80)
            ->assertSet('completed', true);

        $progress = LessonProgress::firstOrFail();
        $this->assertSame(80, $progress->progress_percent);
        $this->assertNotNull($progress->completed_at);

        $this->actingAs($student)
            ->withHeader('Range', 'bytes=0-9')
            ->get(route('files.material.stream', $lesson->primaryMaterial))
            ->assertStatus(206);
    }

    public function test_pdf_is_private_and_requires_explicit_completion_acknowledgement(): void
    {
        Storage::fake('local');
        [$course, $module, $trainer] = $this->managedCourse();

        Livewire::actingAs($trainer)
            ->test(CourseManage::class, ['course' => $course])
            ->call('editLesson', null, $module->id)
            ->set('lessonTitle', 'Required reading')
            ->set('lessonType', 'pdf')
            ->set('lessonStatus', 'published')
            ->set('lessonUpload', UploadedFile::fake()->create('reading.pdf', 100, 'application/pdf'))
            ->call('saveLesson');

        $lesson = Lesson::with('primaryMaterial')->firstOrFail();
        [$student, $enrolment] = $this->enrol($course);
        $outsider = User::factory()->student()->create();

        $this->actingAs($outsider)
            ->get(route('files.material.inline', $lesson->primaryMaterial))
            ->assertForbidden();
        $this->actingAs($student)
            ->get(route('files.material.inline', $lesson->primaryMaterial))
            ->assertOk();
        $this->assertDatabaseMissing('lesson_progress', ['enrolment_id' => $enrolment->id]);

        Livewire::actingAs($student)
            ->test(LessonShow::class, compact('enrolment', 'lesson'))
            ->call('markComplete');

        $this->assertNotNull(LessonProgress::firstOrFail()->acknowledged_at);
    }

    public function test_optional_lesson_does_not_block_certification(): void
    {
        Storage::fake('local');
        $course = Course::factory()->create();
        $module = $this->module($course);
        $required = Lesson::create([
            'course_module_id' => $module->id, 'title' => 'Required', 'type' => 'text',
            'status' => 'published', 'is_required' => true,
        ]);
        Lesson::create([
            'course_module_id' => $module->id, 'title' => 'Optional', 'type' => 'text',
            'status' => 'published', 'is_required' => false,
        ]);
        $this->policy($course, requireLessons: true);
        [$student, $enrolment] = $this->enrol($course);

        Livewire::actingAs($student)
            ->test(LessonShow::class, ['enrolment' => $enrolment, 'lesson' => $required])
            ->call('markComplete');

        $this->assertDatabaseHas('certificates', ['enrolment_id' => $enrolment->id, 'status' => 'issued']);
    }

    public function test_failing_quiz_blocks_and_later_passing_attempt_can_unlock_eligibility(): void
    {
        Storage::fake('local');
        $course = Course::factory()->create();
        $this->policy($course, requireQuizzes: true);
        $quiz = Quiz::create([
            'course_id' => $course->id,
            'creator_id' => User::factory()->trainer()->create()->id,
            'title' => 'Required quiz',
            'max_attempts' => 2,
            'passing_score' => 1,
            'is_required' => true,
            'release_results' => 'immediate',
            'status' => 'published',
        ]);
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id, 'type' => 'mcq', 'prompt' => 'Correct?', 'points' => 1,
        ]);
        $correct = QuizOption::create(['quiz_question_id' => $question->id, 'label' => 'Yes', 'is_correct' => true]);
        $wrong = QuizOption::create(['quiz_question_id' => $question->id, 'label' => 'No', 'is_correct' => false]);
        [$student, $enrolment] = $this->enrol($course);
        $service = app(QuizService::class);

        $this->actingAs($student);
        $failed = $service->startAttempt($quiz, $student);
        $service->answer($failed, $question->id, [$wrong->id]);
        $service->submit($failed);
        $this->assertDatabaseMissing('certificates', ['enrolment_id' => $enrolment->id]);

        $passed = $service->startAttempt($quiz, $student);
        $service->answer($passed, $question->id, [$correct->id]);
        $service->submit($passed);

        $this->assertDatabaseHas('certificates', ['enrolment_id' => $enrolment->id]);
    }

    public function test_pending_assignment_blocks_until_released_passing_grade_and_generates_pdf(): void
    {
        Storage::fake('local');
        $course = Course::factory()->create();
        $this->policy($course, requireAssignments: true);
        $trainer = User::factory()->trainer()->create();
        TrainerAssignment::create(['trainer_id' => $trainer->id, 'course_id' => $course->id]);
        $assignment = Assignment::create([
            'course_id' => $course->id,
            'creator_id' => $trainer->id,
            'title' => 'Required assignment',
            'max_points' => 100,
            'is_required' => true,
            'evaluation_type' => 'numeric',
            'passing_score' => 60,
            'allow_resubmission' => true,
            'max_submissions' => 2,
            'audience' => 'course',
            'status' => 'published',
        ]);
        [$student, $enrolment] = $this->enrol($course);

        Livewire::actingAs($student)
            ->test(AssignmentShow::class, compact('enrolment', 'assignment'))
            ->set('body', 'My submitted work')
            ->call('save')
            ->assertHasNoErrors();

        $submission = $assignment->submissions()->firstOrFail();
        $this->assertSame('missing', app(CertificationEligibilityService::class)->evaluate($enrolment)['status']);
        $this->assertDatabaseMissing('certificates', ['enrolment_id' => $enrolment->id]);

        Livewire::actingAs($trainer)
            ->test(Grading::class, ['assignment' => $assignment])
            ->set("scores.{$submission->id}", 75)
            ->set("feedbacks.{$submission->id}", 'Passed')
            ->call('grade', $submission->id)
            ->assertHasNoErrors();

        $certificate = Certificate::where('enrolment_id', $enrolment->id)->firstOrFail();
        $this->assertNotNull($certificate->pdf_path);
        Storage::disk('local')->assertExists($certificate->pdf_path);
        $this->assertSame(1, $assignment->submissions()->count());
    }

    public function test_repeated_automatic_triggers_keep_one_active_certificate(): void
    {
        Storage::fake('local');
        $course = Course::factory()->create();
        $this->policy($course);
        [, $enrolment] = $this->enrol($course);

        CertificateTrigger::for($enrolment);
        CertificateTrigger::for($enrolment);
        CertificateTrigger::for($enrolment);

        $this->assertSame(1, Certificate::where('enrolment_id', $enrolment->id)->count());
        $this->assertSame(1, Certificate::where('enrolment_id', $enrolment->id)->whereNotNull('active_key')->count());
    }

    public function test_returned_assignment_creates_a_new_attempt_and_keeps_submission_history(): void
    {
        $course = Course::factory()->create();
        $trainer = User::factory()->trainer()->create();
        TrainerAssignment::create(['trainer_id' => $trainer->id, 'course_id' => $course->id]);
        $assignment = Assignment::create([
            'course_id' => $course->id,
            'creator_id' => $trainer->id,
            'title' => 'Revision exercise',
            'max_points' => 100,
            'allow_resubmission' => true,
            'max_submissions' => 2,
            'audience' => 'course',
            'status' => 'published',
        ]);
        [$student, $enrolment] = $this->enrol($course);

        Livewire::actingAs($student)
            ->test(AssignmentShow::class, compact('enrolment', 'assignment'))
            ->set('body', 'First version')
            ->call('save');
        $first = $assignment->submissions()->firstOrFail();

        Livewire::actingAs($trainer)
            ->test(Grading::class, compact('assignment'))
            ->call('returnForRevision', $first->id);

        Livewire::actingAs($student)
            ->test(AssignmentShow::class, compact('enrolment', 'assignment'))
            ->set('body', 'Revised version')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, $assignment->submissions()->count());
        $this->assertDatabaseHas('assignment_submissions', ['id' => $first->id, 'attempt_no' => 1, 'status' => 'returned']);
        $this->assertDatabaseHas('assignment_submissions', ['attempt_no' => 2, 'status' => 'submitted']);
    }

    public function test_optional_quiz_and_assignment_do_not_block_certificate(): void
    {
        Storage::fake('local');
        $course = Course::factory()->create();
        $this->policy($course, requireQuizzes: true, requireAssignments: true);
        Quiz::create([
            'course_id' => $course->id,
            'creator_id' => User::factory()->trainer()->create()->id,
            'title' => 'Optional quiz',
            'is_required' => false,
            'status' => 'published',
        ]);
        Assignment::create([
            'course_id' => $course->id,
            'creator_id' => User::factory()->trainer()->create()->id,
            'title' => 'Optional assignment',
            'max_points' => 100,
            'is_required' => false,
            'audience' => 'course',
            'status' => 'published',
        ]);
        [, $enrolment] = $this->enrol($course);

        CertificateTrigger::for($enrolment);

        $this->assertDatabaseHas('certificates', ['enrolment_id' => $enrolment->id]);
    }

    public function test_expired_enrolment_cannot_stream_private_video_or_receive_certificate(): void
    {
        Storage::fake('local');
        [$course, $module, $trainer] = $this->managedCourse();
        $lesson = Lesson::create([
            'course_module_id' => $module->id, 'title' => 'Video', 'type' => 'video',
            'status' => 'published',
        ]);
        $material = app(FileService::class)->storeLessonContent(
            UploadedFile::fake()->create('video.mp4', 100, 'video/mp4'),
            $trainer,
            $lesson,
            'video',
        );
        $lesson->update(['primary_material_id' => $material->id]);
        $this->policy($course);
        [$student, $enrolment] = $this->enrol($course, ['expires_at' => now()->subMinute()]);

        $this->actingAs($student)->get(route('files.material.stream', $material))->assertForbidden();
        CertificateTrigger::for($enrolment);
        $this->assertDatabaseMissing('certificates', ['enrolment_id' => $enrolment->id]);
    }

    public function test_automatic_trigger_does_not_replace_a_revoked_certificate(): void
    {
        Storage::fake('local');
        $course = Course::factory()->create();
        $this->policy($course);
        [, $enrolment] = $this->enrol($course);
        CertificateTrigger::for($enrolment);
        $certificate = Certificate::firstOrFail();
        app(CertificateIssuanceService::class)->revoke(
            $certificate,
            User::factory()->superAdmin()->create(),
            'Revoked for acceptance test',
        );

        CertificateTrigger::for($enrolment);

        $this->assertSame(1, Certificate::where('enrolment_id', $enrolment->id)->count());
        $this->assertSame('revoked', $certificate->fresh()->status);
    }

    /** @return array{Course, CourseModule, User} */
    private function managedCourse(): array
    {
        $course = Course::factory()->create();
        $module = $this->module($course);
        $trainer = User::factory()->trainer()->create();
        TrainerAssignment::create(['trainer_id' => $trainer->id, 'course_id' => $course->id]);

        return [$course, $module, $trainer];
    }

    private function module(Course $course): CourseModule
    {
        return CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Module one',
            'sort_order' => 1,
            'status' => 'published',
        ]);
    }

    /** @return array{User, Enrolment} */
    private function enrol(Course $course, array $attributes = []): array
    {
        $student = User::factory()->student()->create();
        $variant = CourseVariant::factory()->for($course)->create();
        $enrolment = Enrolment::factory()->create(array_merge([
            'user_id' => $student->id,
            'course_variant_id' => $variant->id,
            'status' => 'active',
        ], $attributes));

        return [$student, $enrolment];
    }

    private function policy(
        Course $course,
        bool $requireLessons = false,
        bool $requireQuizzes = false,
        bool $requireAssignments = false,
    ): CertificationPolicy {
        return CertificationPolicy::create([
            'course_id' => $course->id,
            'enabled' => true,
            'require_lessons' => $requireLessons,
            'require_quizzes' => $requireQuizzes,
            'require_assignments' => $requireAssignments,
            'require_trainer_approval' => false,
            'require_admin_approval' => false,
            'auto_issue' => true,
        ]);
    }
}
