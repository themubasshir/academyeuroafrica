<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrolment;
use App\Models\Lesson;
use App\Models\LiveSession;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\TrainerAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AcceptanceFixtureCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_acceptance_fixture_is_local_only_and_idempotent(): void
    {
        Storage::fake('local');

        $this->artisan('academy:acceptance-fixture')->assertSuccessful();
        $this->artisan('academy:acceptance-fixture')->assertSuccessful();

        $course = Course::where('slug', 'acceptance-browser-course')->firstOrFail();
        $student = User::where('email', 'acceptance-student@eamn.test')->firstOrFail();
        $trainer = User::where('email', 'acceptance-trainer@eamn.test')->firstOrFail();

        $this->assertTrue($student->hasRole(Role::STUDENT));
        $this->assertTrue($trainer->hasRole(Role::TRAINER));
        $this->assertSame(1, Enrolment::where('user_id', $student->id)->whereHas('variant', fn ($query) => $query->where('course_id', $course->id))->count());
        $this->assertSame(1, TrainerAssignment::where('trainer_id', $trainer->id)->where('course_id', $course->id)->count());
        $this->assertSame(2, Lesson::whereHas('module', fn ($query) => $query->where('course_id', $course->id))->count());
        $this->assertSame(1, Assignment::where('course_id', $course->id)->count());
        $this->assertSame(1, Quiz::where('course_id', $course->id)->count());
        $this->assertSame(2, Material::where('attachable_id', $course->id)->where('attachable_type', Course::class)->count());
        $this->assertSame(1, LiveSession::where('course_id', $course->id)->where('status', 'scheduled')->count());
        Storage::disk('local')->assertExists('acceptance/student-material.txt');
        Storage::disk('local')->assertExists('acceptance/trainer-material.txt');
    }

    public function test_acceptance_fixture_refuses_non_local_environment(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('academy:acceptance-fixture')->assertFailed();
    }
}
