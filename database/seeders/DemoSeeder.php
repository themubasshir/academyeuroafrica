<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseVariant;
use App\Models\Enrolment;
use App\Models\Lesson;
use App\Models\Programme;
use App\Models\TrainerAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo data for local UI development / screenshots only.
 * Run: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $mk = function (string $role, string $name, string $email) {
            $user = User::firstOrCreate(['email' => $email], ['name' => $name, 'password' => bcrypt('password'), 'status' => 'active']);
            $user->assignRole($role);

            return $user;
        };
        $student = $mk('student', 'Ada Student', 'student@eamn.dev');
        $trainer = $mk('trainer', 'Kwame Trainer', 'trainer@eamn.dev');
        $mk('academy-admin', 'Admin', 'admin@eamn.dev');
        $mk('super-admin', 'Super', 'super@eamn.dev');

        $programme = Programme::firstOrCreate(['slug' => 'broadcast-journalism'], [
            'name' => 'Broadcast Journalism',
            'description' => 'Radio, TV and digital broadcast skills for African and European media.',
            'status' => 'published',
        ]);
        Programme::firstOrCreate(['slug' => 'digital-media'], ['name' => 'Digital Media Production', 'description' => 'Content creation for web and social.', 'status' => 'published']);

        $course = Course::firstOrCreate(['slug' => 'radio-presentation'], [
            'programme_id' => $programme->id, 'title' => 'Radio Presentation Fundamentals',
            'status' => 'published',
            'description' => 'Master microphone technique, scripting and live delivery for radio.',
            'delivery_types' => ['live', 'prerecorded'],
        ]);
        Course::firstOrCreate(['slug' => 'tv-studio'], ['programme_id' => $programme->id, 'title' => 'TV Studio Operations', 'status' => 'published', 'delivery_types' => ['live']]);

        $variant = CourseVariant::firstOrCreate(['course_id' => $course->id, 'name' => 'Live cohort'], [
            'delivery_type' => 'live', 'price_cents' => 12900, 'status' => 'published',
        ]);
        CourseVariant::firstOrCreate(['course_id' => $course->id, 'name' => 'Self-paced'], ['delivery_type' => 'prerecorded', 'price_cents' => 8900, 'status' => 'published']);

        $m1 = CourseModule::firstOrCreate(['course_id' => $course->id, 'title' => 'Getting started'], ['sort_order' => 1, 'status' => 'published']);
        Lesson::firstOrCreate(['course_module_id' => $m1->id, 'title' => 'Welcome & course tour'], ['sort_order' => 1, 'status' => 'published', 'type' => 'text', 'content' => 'Introduction to the course.']);
        Lesson::firstOrCreate(['course_module_id' => $m1->id, 'title' => 'Your voice as an instrument'], ['sort_order' => 2, 'status' => 'published', 'type' => 'video']);

        TrainerAssignment::firstOrCreate(['trainer_id' => $trainer->id, 'course_id' => $course->id]);
        Enrolment::firstOrCreate(['user_id' => $student->id, 'course_variant_id' => $variant->id], ['status' => 'active', 'enrolled_at' => now()]);
    }
}
