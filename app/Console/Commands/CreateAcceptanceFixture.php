<?php

namespace App\Console\Commands;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseVariant;
use App\Models\Enrolment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LiveSession;
use App\Models\Material;
use App\Models\Programme;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\Role;
use App\Models\TrainerAssignment;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

#[Signature('academy:acceptance-fixture')]
#[Description('Create deterministic local-only browser acceptance fixtures.')]
class CreateAcceptanceFixture extends Command
{
    private const PASSWORD = 'Acceptance#2026!';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Acceptance fixture is local/testing only.');

            return self::FAILURE;
        }

        app(RoleSeeder::class)->run();

        $admin = $this->user('Acceptance Admin', 'acceptance-admin@eamn.test', Role::ACADEMY_ADMIN);
        $trainer = $this->user('Acceptance Trainer', 'acceptance-trainer@eamn.test', Role::TRAINER);
        $student = $this->user('Acceptance Student', 'acceptance-student@eamn.test', Role::STUDENT);
        $otherStudent = $this->user('Acceptance Other Student', 'acceptance-other-student@eamn.test', Role::STUDENT);

        $programme = Programme::updateOrCreate(['slug' => 'acceptance-browser-programme'], [
            'name' => 'Acceptance Browser Programme',
            'description' => 'Local-only deterministic browser acceptance fixture.',
            'status' => 'published',
            'sort_order' => 9990,
        ]);
        $course = Course::updateOrCreate(['slug' => 'acceptance-browser-course'], [
            'programme_id' => $programme->id,
            'title' => 'Acceptance Browser Course',
            'description' => 'Complete local-only course for repeatable browser testing.',
            'delivery_types' => ['live', 'prerecorded'],
            'status' => 'published',
            'sort_order' => 9990,
        ]);
        $unassignedCourse = Course::updateOrCreate(['slug' => 'acceptance-unassigned-course'], [
            'programme_id' => $programme->id,
            'title' => 'Acceptance Unassigned Course',
            'description' => 'Local-only authorization negative-test course.',
            'delivery_types' => ['prerecorded'],
            'status' => 'published',
            'sort_order' => 9991,
        ]);
        $variant = CourseVariant::updateOrCreate(['source_key' => 'acceptance-browser-course-standard'], [
            'course_id' => $course->id,
            'name' => 'Acceptance Standard',
            'delivery_type' => 'prerecorded',
            'price_cents' => 100,
            'currency' => 'EUR',
            'status' => 'draft',
            'approval_status' => 'pending',
            'checkout_ref' => 'acceptance-browser-course-standard',
        ]);
        TrainerAssignment::updateOrCreate(['trainer_id' => $trainer->id, 'course_id' => $course->id]);

        $module = CourseModule::updateOrCreate(['course_id' => $course->id, 'title' => 'Acceptance Module'], [
            'sort_order' => 1,
            'status' => 'published',
        ]);
        $lesson = Lesson::updateOrCreate(['course_module_id' => $module->id, 'title' => 'Acceptance Lesson'], [
            'content' => 'This local-only lesson verifies browser completion persistence.',
            'type' => 'text',
            'sort_order' => 1,
            'status' => 'published',
        ]);
        Lesson::updateOrCreate(['course_module_id' => $module->id, 'title' => 'Acceptance Follow-up Lesson'], [
            'content' => 'Second local-only lesson ensures measurable progress.',
            'type' => 'text',
            'sort_order' => 2,
            'status' => 'published',
        ]);

        $enrolment = Enrolment::updateOrCreate(['user_id' => $student->id, 'course_variant_id' => $variant->id], [
            'status' => 'active',
            'enrolled_at' => now(),
            'expires_at' => null,
        ]);
        Enrolment::updateOrCreate(['user_id' => $otherStudent->id, 'course_variant_id' => $variant->id], [
            'status' => 'active',
            'enrolled_at' => now(),
            'expires_at' => null,
        ]);

        $assignment = Assignment::updateOrCreate(['course_id' => $course->id, 'title' => 'Acceptance Assignment'], [
            'creator_id' => $trainer->id,
            'instructions' => 'Submit this deterministic browser acceptance response.',
            'due_at' => now()->addWeek(),
            'max_points' => 10,
            'audience' => 'course',
            'status' => 'published',
            'grades_released' => true,
        ]);
        $quiz = Quiz::updateOrCreate(['course_id' => $course->id, 'title' => 'Acceptance Quiz'], [
            'creator_id' => $trainer->id,
            'instructions' => 'Select the valid deterministic answer.',
            'max_attempts' => 1,
            'passing_score' => 50,
            'randomise_questions' => false,
            'randomise_options' => false,
            'release_results' => 'immediate',
            'status' => 'published',
        ]);
        $question = QuizQuestion::updateOrCreate(['quiz_id' => $quiz->id, 'prompt' => 'Which answer validates acceptance?'], [
            'type' => QuizQuestion::TYPE_MCQ,
            'points' => 10,
            'sort_order' => 1,
        ]);
        QuizOption::updateOrCreate(['quiz_question_id' => $question->id, 'label' => 'Deterministic fixture'], [
            'is_correct' => true,
            'sort_order' => 1,
        ]);
        QuizOption::updateOrCreate(['quiz_question_id' => $question->id, 'label' => 'Production data'], [
            'is_correct' => false,
            'sort_order' => 2,
        ]);

        $this->material($course, $trainer, 'acceptance/student-material.txt', 'acceptance-student-material.txt', 'enrolled', 'Student material');
        $this->material($course, $trainer, 'acceptance/trainer-material.txt', 'acceptance-trainer-material.txt', 'trainers', 'Trainer-only material');

        $session = LiveSession::updateOrCreate(['course_id' => $course->id, 'title' => 'Acceptance Live Lesson'], [
            'trainer_id' => $trainer->id,
            'starts_at' => now()->addDay(),
            'timezone' => 'UTC',
            'duration_minutes' => 60,
            'provider' => 'zoom',
            'meeting_url' => 'https://example.test/acceptance-live-lesson',
            'status' => 'scheduled',
        ]);

        // Reset mutable state so repeated runs are deterministic.
        LessonProgress::where('enrolment_id', $enrolment->id)->delete();
        AssignmentSubmission::where('assignment_id', $assignment->id)->where('user_id', $student->id)->delete();
        QuizAttempt::where('quiz_id', $quiz->id)->where('user_id', $student->id)->delete();
        CourseModule::where('course_id', $course->id)->where('title', 'Browser Added Module')->delete();

        $this->line(json_encode([
            'admin' => $admin->email,
            'trainer' => $trainer->email,
            'student' => $student->email,
            'course_id' => $course->id,
            'unassigned_course_id' => $unassignedCourse->id,
            'trainer_material_id' => Material::where('path', 'acceptance/trainer-material.txt')->value('id'),
            'enrolment_id' => $enrolment->id,
            'lesson_id' => $lesson->id,
            'assignment_id' => $assignment->id,
            'quiz_id' => $quiz->id,
            'session_id' => $session->id,
        ], JSON_THROW_ON_ERROR));
        $this->info('Acceptance fixture ready. Password omitted from output.');

        return self::SUCCESS;
    }

    private function user(string $name, string $email, string $role): User
    {
        $user = User::firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $name,
            'password' => Hash::make(self::PASSWORD),
            'status' => 'active',
            'disabled_at' => null,
            'email_verified_at' => now(),
        ])->save();
        $user->assignRole($role);

        return $user;
    }

    private function material(Course $course, User $trainer, string $path, string $name, string $visibility, string $description): void
    {
        Storage::disk('local')->put($path, "EAMN acceptance fixture: {$visibility} material.\n");
        Material::updateOrCreate(['path' => $path], [
            'uploader_id' => $trainer->id,
            'attachable_type' => Course::class,
            'attachable_id' => $course->id,
            'disk' => 'local',
            'original_name' => $name,
            'description' => $description,
            'mime' => 'text/plain',
            'size_bytes' => Storage::disk('local')->size($path),
            'visibility' => $visibility,
            'group_id' => null,
            'student_id' => null,
        ]);
    }
}
