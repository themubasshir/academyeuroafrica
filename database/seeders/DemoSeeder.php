<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentGrade;
use App\Models\AssignmentSubmission;
use App\Models\CertificationPolicy;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseVariant;
use App\Models\Enrolment;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LiveSession;
use App\Models\Material;
use App\Models\Programme;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\Role;
use App\Models\SessionCreditLedger;
use App\Models\TrainerAssignment;
use App\Models\TrainerAvailability;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Demo data for local UI development / screenshots only.
 * Refuses to run outside local/testing so demo credentials can never
 * reach production. Run: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'EamnDemo#2026!';

    public function run(): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403, 'DemoSeeder is local/testing only.');

        $this->call(RoleSeeder::class);

        $mk = function (string $role, string $name, string $email): User {
            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => bcrypt(self::DEMO_PASSWORD), 'status' => 'active']
            );
            if ($user->status !== 'active' || $user->disabled_at !== null || $user->email_verified_at === null) {
                $user->forceFill(['status' => 'active', 'disabled_at' => null, 'email_verified_at' => $user->email_verified_at ?? now()])->save();
            }
            $user->assignRole($role);

            return $user;
        };

        $student = $mk(Role::STUDENT, 'Ada Student', 'student@eamn.test');
        $trainer = $mk(Role::TRAINER, 'Kwame Trainer', 'trainer@eamn.test');
        $admin = $mk(Role::ACADEMY_ADMIN, 'Amara Admin', 'admin@eamn.test');
        // Legacy dev accounts from earlier seeding rounds — keep working.
        foreach ([['student', 'Ada Student', 'student@eamn.dev'], ['trainer', 'Kwame Trainer', 'trainer@eamn.dev'], ['academy-admin', 'Admin', 'admin@eamn.dev'], ['super-admin', 'Super', 'super@eamn.dev']] as [$role, $name, $email]) {
            $legacy = User::where('email', $email)->first();
            if ($legacy) {
                $legacy->assignRole($role);
            }
        }

        $broadcast = Programme::firstOrCreate(['slug' => 'broadcast-journalism'], [
            'name' => 'Broadcast Journalism',
            'description' => 'Radio, TV and digital broadcast skills for African and European media.',
            'status' => 'published', 'sort_order' => 1,
        ]);
        Programme::firstOrCreate(['slug' => 'digital-media'], [
            'name' => 'Digital Media Production',
            'description' => 'Content creation for web and social.',
            'status' => 'published', 'sort_order' => 2,
        ]);

        $radio = Course::firstOrCreate(['slug' => 'radio-presentation'], [
            'programme_id' => $broadcast->id,
            'title' => 'Radio Presentation Fundamentals',
            'status' => 'published',
            'description' => 'Master microphone technique, scripting and live delivery for radio.',
            'delivery_types' => ['live', 'prerecorded'],
            'sort_order' => 1,
        ]);
        $tv = Course::firstOrCreate(['slug' => 'tv-studio'], [
            'programme_id' => $broadcast->id,
            'title' => 'TV Studio Operations',
            'status' => 'published',
            'description' => 'Camera, lighting, floor management and gallery workflow.',
            'delivery_types' => ['live'],
            'sort_order' => 2,
        ]);

        $liveVariant = CourseVariant::firstOrCreate(['course_id' => $radio->id, 'name' => 'Live Online Training'], [
            'delivery_type' => 'live', 'price_cents' => 12900, 'currency' => 'EUR', 'capacity' => 20, 'status' => 'published',
        ]);
        $selfVariant = CourseVariant::firstOrCreate(['course_id' => $radio->id, 'name' => 'Self-paced'], [
            'delivery_type' => 'prerecorded', 'price_cents' => 8900, 'currency' => 'EUR', 'status' => 'published',
        ]);
        $tvVariant = CourseVariant::firstOrCreate(['course_id' => $tv->id, 'name' => 'Live Online Training'], [
            'delivery_type' => 'live', 'price_cents' => 14900, 'currency' => 'EUR', 'capacity' => 12, 'status' => 'published',
        ]);

        // Modules + lessons -------------------------------------------------
        $m1 = CourseModule::firstOrCreate(['course_id' => $radio->id, 'title' => 'Getting started'], ['sort_order' => 1, 'status' => 'published']);
        $m2 = CourseModule::firstOrCreate(['course_id' => $radio->id, 'title' => 'On-air technique'], ['sort_order' => 2, 'status' => 'published']);
        $m3 = CourseModule::firstOrCreate(['course_id' => $radio->id, 'title' => 'Assessment'], ['sort_order' => 3, 'status' => 'published']);

        $l1 = Lesson::firstOrCreate(['course_module_id' => $m1->id, 'title' => 'Welcome & course tour'], [
            'sort_order' => 1, 'status' => 'published', 'type' => 'text',
            'content' => 'Introduction to the course, the register, and how your progress is recorded.',
        ]);
        $l2 = Lesson::firstOrCreate(['course_module_id' => $m1->id, 'title' => 'Your voice as an instrument'], [
            'sort_order' => 2, 'status' => 'published', 'type' => 'video',
        ]);
        $l3 = Lesson::firstOrCreate(['course_module_id' => $m2->id, 'title' => 'Microphone discipline'], [
            'sort_order' => 1, 'status' => 'published', 'type' => 'video', 'requires_previous' => true,
        ]);
        $l4 = Lesson::firstOrCreate(['course_module_id' => $m2->id, 'title' => 'Scripting a 3-minute segment'], [
            'sort_order' => 2, 'status' => 'published', 'type' => 'text',
            'content' => 'Structure, timing marks and read-through practice.',
        ]);
        Lesson::firstOrCreate(['course_module_id' => $m3->id, 'title' => 'Final recorded bulletin'], [
            'sort_order' => 1, 'status' => 'published', 'type' => 'assignment', 'requires_previous' => true,
        ]);

        $tvM1 = CourseModule::firstOrCreate(['course_id' => $tv->id, 'title' => 'Studio fundamentals'], ['sort_order' => 1, 'status' => 'published']);
        Lesson::firstOrCreate(['course_module_id' => $tvM1->id, 'title' => 'Studio safety & etiquette'], [
            'sort_order' => 1, 'status' => 'published', 'type' => 'text', 'content' => 'Cables, headsets, cues and floor signals.',
        ]);

        // Group (needed before materials targeting) --------------------------
        $group = Group::firstOrCreate(['course_id' => $radio->id, 'name' => 'Cohort A — March'], ['trainer_id' => $trainer->id]);

        // Materials ---------------------------------------------------------
        // Write real files to the private disk so downloads actually work.
        $mkFile = function (string $path, string $body) {
            if (! Storage::disk('local')->exists($path)) {
                Storage::disk('local')->put($path, $body);
            }

            return Storage::disk('local')->size($path);
        };
        $pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Size 1>>\n%%EOF\n";

        Material::firstOrCreate([
            'attachable_type' => Course::class, 'attachable_id' => $radio->id,
            'original_name' => 'course-handbook.pdf',
        ], [
            'uploader_id' => $trainer->id, 'disk' => 'local', 'path' => 'materials/course-handbook.pdf',
            'description' => 'Course handbook — policies, reading list, schedule.',
            'mime' => 'application/pdf', 'size_bytes' => $mkFile('materials/course-handbook.pdf', $pdf),
            'visibility' => 'enrolled',
        ]);
        Material::firstOrCreate([
            'attachable_type' => Course::class, 'attachable_id' => $radio->id,
            'original_name' => 'microphone-setup-guide.pdf',
        ], [
            'uploader_id' => $trainer->id, 'disk' => 'local', 'path' => 'materials/mic-setup.pdf',
            'description' => 'Mic positioning and gain staging.',
            'mime' => 'application/pdf', 'size_bytes' => $mkFile('materials/mic-setup.pdf', $pdf),
            'visibility' => 'enrolled',
        ]);
        Material::firstOrCreate([
            'attachable_type' => Lesson::class, 'attachable_id' => $l4->id,
            'original_name' => 'segment-template.pdf',
        ], [
            'uploader_id' => $trainer->id, 'disk' => 'local', 'path' => 'materials/segment-template.pdf',
            'description' => 'Fill-in template for the 3-minute segment.',
            'mime' => 'application/pdf', 'size_bytes' => $mkFile('materials/segment-template.pdf', $pdf),
            'visibility' => 'enrolled',
        ]);

        // Group-targeted and student-targeted materials.
        Material::firstOrCreate([
            'attachable_type' => Course::class, 'attachable_id' => $radio->id,
            'original_name' => 'cohort-a-briefing.pdf',
        ], [
            'uploader_id' => $trainer->id, 'disk' => 'local', 'path' => 'materials/cohort-a-briefing.pdf',
            'description' => 'Briefing pack for Cohort A only.',
            'mime' => 'application/pdf', 'size_bytes' => $mkFile('materials/cohort-a-briefing.pdf', $pdf),
            'visibility' => 'enrolled',
            'group_id' => $group->id,
        ]);
        Material::firstOrCreate([
            'attachable_type' => Course::class, 'attachable_id' => $radio->id,
            'original_name' => 'ada-progress-feedback.pdf',
        ], [
            'uploader_id' => $trainer->id, 'disk' => 'local', 'path' => 'materials/ada-progress-feedback.pdf',
            'description' => 'Individual feedback on your first bulletin.',
            'mime' => 'application/pdf', 'size_bytes' => $mkFile('materials/ada-progress-feedback.pdf', $pdf),
            'visibility' => 'enrolled',
            'student_id' => $student->id,
        ]);

        // Certification policy on the Radio course ---------------------------
        CertificationPolicy::firstOrCreate(['course_id' => $radio->id], [
            'enabled' => true,
            'require_lessons' => true,
            'require_quizzes' => true,
            'require_assignments' => false,
            'auto_issue' => true,
            'signatory_name' => 'Academic Board',
            'signatory_title' => 'EuroAfrica Media Academy',
        ]);

        // Trainer assignment + availability --------------------------------
        TrainerAssignment::firstOrCreate(['trainer_id' => $trainer->id, 'course_id' => $radio->id]);
        TrainerAssignment::firstOrCreate(['trainer_id' => $trainer->id, 'course_id' => $tv->id]);

        foreach ([1, 3, 5] as $weekday) {
            TrainerAvailability::firstOrCreate([
                'trainer_id' => $trainer->id,
                'starts_at' => now()->next($weekday === 1 ? 'monday' : ($weekday === 3 ? 'wednesday' : 'friday'))->setTime(14, 0),
            ], [
                'ends_at' => now()->next($weekday === 1 ? 'monday' : ($weekday === 3 ? 'wednesday' : 'friday'))->setTime(16, 0),
                'timezone' => 'Europe/Brussels',
            ]);
        }

        // Enrolments --------------------------------------------------------
        $enrolment = Enrolment::firstOrCreate(['user_id' => $student->id, 'course_variant_id' => $liveVariant->id], [
            'status' => 'active', 'enrolled_at' => now()->subWeeks(2),
        ]);
        Enrolment::firstOrCreate(['user_id' => $student->id, 'course_variant_id' => $tvVariant->id], [
            'status' => 'active', 'enrolled_at' => now()->subWeek(),
        ]);
        GroupMember::firstOrCreate(['group_id' => $group->id, 'user_id' => $student->id]);

        // Progress ----------------------------------------------------------
        LessonProgress::firstOrCreate(['enrolment_id' => $enrolment->id, 'lesson_id' => $l1->id], [
            'completed_at' => now()->subDays(10), 'position_seconds' => 0,
        ]);
        LessonProgress::firstOrCreate(['enrolment_id' => $enrolment->id, 'lesson_id' => $l2->id], [
            'completed_at' => now()->subDays(6), 'position_seconds' => 412,
        ]);
        LessonProgress::firstOrCreate(['enrolment_id' => $enrolment->id, 'lesson_id' => $l3->id], [
            'position_seconds' => 90,
        ]);

        // Sessions ----------------------------------------------------------
        LiveSession::firstOrCreate(['course_id' => $radio->id, 'title' => 'Voice warm-up lab'], [
            'trainer_id' => $trainer->id, 'group_id' => $group->id,
            'starts_at' => now()->subDays(4)->setTime(15, 0), 'timezone' => 'Europe/Brussels',
            'duration_minutes' => 60, 'provider' => 'zoom', 'status' => 'completed',
            'meeting_url' => 'https://zoom.example.com/eamn-warmup',
            'recording_url' => 'https://recordings.example.com/eamn-warmup',
        ]);
        LiveSession::firstOrCreate(['course_id' => $radio->id, 'title' => 'Script critique circle'], [
            'trainer_id' => $trainer->id, 'group_id' => $group->id,
            'starts_at' => now()->addDays(3)->setTime(15, 0), 'timezone' => 'Europe/Brussels',
            'duration_minutes' => 60, 'provider' => 'zoom', 'status' => 'scheduled',
            'meeting_url' => 'https://zoom.example.com/eamn-critique',
        ]);
        LiveSession::firstOrCreate(['course_id' => $tv->id, 'title' => 'Gallery walkthrough'], [
            'trainer_id' => $trainer->id,
            'starts_at' => now()->addDays(6)->setTime(10, 0), 'timezone' => 'Europe/Brussels',
            'duration_minutes' => 90, 'provider' => 'zoom', 'status' => 'scheduled',
            'meeting_url' => 'https://zoom.example.com/eamn-gallery',
        ]);

        // Assignment + submission + grade -----------------------------------
        $assignment = Assignment::firstOrCreate(['course_id' => $radio->id, 'title' => 'Record a 90-second news link'], [
            'creator_id' => $trainer->id,
            'instructions' => 'Introduce the story, hand to the correspondent, and close cleanly.',
            'due_at' => now()->addWeek(), 'max_points' => 20,
            'audience' => 'enrolled', 'status' => 'published', 'grades_released' => true,
        ]);
        $submission = AssignmentSubmission::firstOrCreate(['assignment_id' => $assignment->id, 'user_id' => $student->id], [
            'body' => 'Attached my recorded link — kept it to 88 seconds.',
            'submitted_at' => now()->subDays(3), 'status' => 'submitted',
        ]);
        AssignmentGrade::firstOrCreate(['assignment_submission_id' => $submission->id], [
            'grader_id' => $trainer->id, 'score' => 17, 'status' => 'released',
            'feedback' => 'Strong hand-off; watch the pause before the close.',
        ]);

        // Quiz + attempt ----------------------------------------------------
        $quiz = Quiz::firstOrCreate(['course_id' => $radio->id, 'title' => 'On-air fundamentals check'], [
            'creator_id' => $trainer->id, 'instructions' => 'Five questions on microphone and scripting basics.',
            'time_limit_minutes' => 10, 'max_attempts' => 3, 'passing_score' => 60,
            'randomise_questions' => false, 'randomise_options' => false,
            'release_results' => 'immediate', 'status' => 'published',
        ]);

        $q1 = QuizQuestion::firstOrCreate(['quiz_id' => $quiz->id, 'prompt' => 'How far should the mic sit from your mouth?'], ['type' => 'single', 'points' => 2, 'sort_order' => 1]);
        QuizOption::firstOrCreate(['quiz_question_id' => $q1->id, 'label' => 'A fist-width away'], ['is_correct' => true, 'sort_order' => 1]);
        QuizOption::firstOrCreate(['quiz_question_id' => $q1->id, 'label' => 'Lips touching the grille'], ['is_correct' => false, 'sort_order' => 2]);
        QuizOption::firstOrCreate(['quiz_question_id' => $q1->id, 'label' => 'Half a metre back'], ['is_correct' => false, 'sort_order' => 3]);

        $q2 = QuizQuestion::firstOrCreate(['quiz_id' => $quiz->id, 'prompt' => 'What marks timing on a script?'], ['type' => 'single', 'points' => 2, 'sort_order' => 2]);
        QuizOption::firstOrCreate(['quiz_question_id' => $q2->id, 'label' => 'Timing marks in the margin'], ['is_correct' => true, 'sort_order' => 1]);
        QuizOption::firstOrCreate(['quiz_question_id' => $q2->id, 'label' => 'Nothing — you guess'], ['is_correct' => false, 'sort_order' => 2]);

        $attempt = QuizAttempt::firstOrCreate(['quiz_id' => $quiz->id, 'user_id' => $student->id, 'attempt_no' => 1], [
            'started_at' => now()->subDays(5), 'submitted_at' => now()->subDays(5)->addMinutes(4),
            'score' => 100, 'status' => 'submitted',
            'question_order' => [$q1->id, $q2->id],
        ]);
        QuizAnswer::firstOrCreate(['quiz_attempt_id' => $attempt->id, 'quiz_question_id' => $q1->id], ['chosen_option_ids' => [$q1->options->first()->id], 'awarded_points' => 2]);
        QuizAnswer::firstOrCreate(['quiz_attempt_id' => $attempt->id, 'quiz_question_id' => $q2->id], ['chosen_option_ids' => [$q2->options->first()->id], 'awarded_points' => 2]);

        // Credit ledger -----------------------------------------------------
        SessionCreditLedger::firstOrCreate(['user_id' => $student->id, 'reason' => 'bundle_purchase'], ['delta' => 4]);
        SessionCreditLedger::firstOrCreate(['user_id' => $student->id, 'reason' => 'session_booked'], ['delta' => -1]);

        // Announcement ------------------------------------------------------
        Announcement::firstOrCreate(['course_id' => $radio->id, 'title' => 'Welcome to Cohort A'], [
            'author_id' => $trainer->id, 'group_id' => $group->id,
            'body' => 'Sessions run Mondays and Wednesdays. Bring a printed script to the critique circle.',
        ]);
    }
}
