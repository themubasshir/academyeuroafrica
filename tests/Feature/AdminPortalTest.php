<?php

namespace Tests\Feature;

use App\Livewire\Admin\Courses;
use App\Livewire\Admin\Enrolments;
use App\Livewire\Admin\Materials;
use App\Livewire\Admin\Programmes;
use App\Livewire\Admin\Users;
use App\Models\Course;
use App\Models\CourseVariant;
use App\Models\Enrolment;
use App\Models\Material;
use App\Models\Order;
use App\Models\Programme;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_and_invite_student_without_password(): void
    {
        Notification::fake();
        $admin = User::factory()->academyAdmin()->create();

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('create')
            ->set('name', 'Manual Student')
            ->set('email', 'manual.student@example.test')
            ->set('selectedRoles', [Role::STUDENT])
            ->call('save')
            ->assertHasNoErrors();

        $student = User::where('email', 'manual.student@example.test')->firstOrFail();
        $this->assertNull($student->password);
        $this->assertTrue($student->fresh()->hasRole(Role::STUDENT));
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.created', 'subject_id' => $student->id]);
    }

    public function test_academy_admin_cannot_promote_user_to_super_admin(): void
    {
        $admin = User::factory()->academyAdmin()->create();
        $student = User::factory()->student()->create();

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('edit', $student->id)
            ->set('selectedRoles', [Role::SUPER_ADMIN])
            ->call('save')
            ->assertStatus(403);

        $this->assertFalse($student->fresh()->hasRole(Role::SUPER_ADMIN));
    }

    public function test_manual_enrolment_records_source_and_reason(): void
    {
        $admin = User::factory()->academyAdmin()->create();
        $student = User::factory()->student()->create();
        $variant = CourseVariant::factory()->create();

        Livewire::actingAs($admin)->test(Enrolments::class)
            ->set('userId', $student->id)
            ->set('variantId', $variant->id)
            ->set('grantReason', 'Scholarship')
            ->call('grant');

        $this->assertDatabaseHas('enrolments', ['user_id' => $student->id, 'source' => 'manual', 'grant_reason' => 'Scholarship', 'order_id' => null]);
    }

    public function test_non_administrators_cannot_access_new_admin_surfaces(): void
    {
        $student = User::factory()->student()->create();

        foreach (['admin.materials', 'admin.reports'] as $route) {
            $this->actingAs($student)->get(route($route))->assertForbidden();
        }
    }

    public function test_administrator_creates_and_archives_a_programme(): void
    {
        $admin = User::factory()->academyAdmin()->create();

        Livewire::actingAs($admin)
            ->test(Programmes::class)
            ->set('name', 'Clinical Communication')
            ->set('description', 'A programme description.')
            ->set('status', 'published')
            ->call('save')
            ->assertHasNoErrors();

        $programme = Programme::firstOrFail();
        $this->assertDatabaseHas('programmes', ['id' => $programme->id, 'status' => 'published']);

        Livewire::actingAs($admin)->test(Programmes::class)->call('archive', $programme->id);
        $this->assertDatabaseHas('programmes', ['id' => $programme->id, 'status' => 'archived']);
    }

    public function test_administrator_creates_course_and_assigns_trainer(): void
    {
        $admin = User::factory()->academyAdmin()->create();
        $trainer = User::factory()->trainer()->create();
        $programme = Programme::factory()->create();

        Livewire::actingAs($admin)
            ->test(Courses::class)
            ->set('title', 'Managing Difficult Conversations')
            ->set('programmeId', $programme->id)
            ->set('description', 'Course summary')
            ->set('learningOutcomes', "Listen actively\nRespond confidently")
            ->set('deliveryTypes', 'live, hybrid')
            ->set('status', 'draft')
            ->set('trainerId', $trainer->id)
            ->call('save')
            ->assertHasNoErrors();

        $course = Course::firstOrFail();
        $this->assertSame(['Listen actively', 'Respond confidently'], $course->learning_outcomes);
        $this->assertDatabaseHas('trainer_assignments', ['course_id' => $course->id, 'trainer_id' => $trainer->id]);
    }

    public function test_administrator_cannot_change_commercial_enrolment_manually(): void
    {
        $admin = User::factory()->academyAdmin()->create();
        $student = User::factory()->student()->create();
        $order = Order::create([
            'user_id' => $student->id,
            'reference' => 'ORD-COMMERCIAL-1',
            'status' => 'paid',
            'total_cents' => 1000,
            'currency' => 'EUR',
        ]);
        $enrolment = Enrolment::factory()->for($student)->create(['order_id' => $order->id, 'status' => 'active']);

        Livewire::actingAs($admin)
            ->test(Enrolments::class)
            ->call('suspend', $enrolment->id)
            ->assertStatus(422);

        $this->assertDatabaseHas('enrolments', ['id' => $enrolment->id, 'status' => 'active']);
    }

    public function test_administrator_grants_manual_enrolment_without_duplicate_record(): void
    {
        $admin = User::factory()->academyAdmin()->create();
        $student = User::factory()->student()->create();
        $variant = CourseVariant::factory()->create();

        Livewire::actingAs($admin)
            ->test(Enrolments::class)
            ->set('userId', $student->id)
            ->set('variantId', $variant->id)
            ->set('grantReason', 'Scholarship approved by academy administrator')
            ->call('grant')
            ->assertHasNoErrors();

        Livewire::actingAs($admin)
            ->test(Enrolments::class)
            ->set('userId', $student->id)
            ->set('variantId', $variant->id)
            ->set('grantReason', 'Scholarship approved by academy administrator')
            ->call('grant')
            ->assertHasNoErrors();

        $this->assertSame(1, Enrolment::where('user_id', $student->id)->where('course_variant_id', $variant->id)->count());
    }

    public function test_last_super_admin_cannot_be_disabled_or_demoted(): void
    {
        $admin = User::factory()->superAdmin()->create();

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('toggleActive', $admin->id)
            ->assertStatus(422);

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('edit', $admin->id)
            ->set('selectedRoles', [Role::ACADEMY_ADMIN])
            ->set('timezone', 'Europe/Lisbon')
            ->call('save');

        $this->assertTrue($admin->fresh()->hasRole(Role::SUPER_ADMIN));
        $this->assertSame('active', $admin->fresh()->status);
    }

    public function test_administrator_deletes_private_material_and_underlying_file(): void
    {
        Storage::fake('local');
        $admin = User::factory()->academyAdmin()->create();
        $course = Course::factory()->create();
        Storage::disk('local')->put('materials/admin-only.pdf', 'private');
        $material = Material::create([
            'uploader_id' => $admin->id,
            'attachable_type' => Course::class,
            'attachable_id' => $course->id,
            'disk' => 'local',
            'path' => 'materials/admin-only.pdf',
            'original_name' => 'admin-only.pdf',
            'mime' => 'application/pdf',
            'size_bytes' => 7,
            'visibility' => 'trainers',
        ]);

        Livewire::actingAs($admin)->test(Materials::class)->call('delete', $material->id);

        $this->assertDatabaseMissing('materials', ['id' => $material->id]);
        Storage::disk('local')->assertMissing('materials/admin-only.pdf');
    }
}
