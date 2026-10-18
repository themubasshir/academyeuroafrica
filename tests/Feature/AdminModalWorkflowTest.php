<?php

namespace Tests\Feature;

use App\Livewire\Admin\Courses;
use App\Livewire\Admin\Programmes;
use App\Models\Course;
use App\Models\Programme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminModalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_programme_create_edit_validation_and_cancel_workflow(): void
    {
        $admin = User::factory()->academyAdmin()->create();

        $component = Livewire::actingAs($admin)->test(Programmes::class)
            ->call('createProgramme')
            ->assertSet('showProgrammeModal', true)
            ->call('save')
            ->assertHasErrors(['name'])
            ->assertSet('showProgrammeModal', true)
            ->set('name', 'Editorial Leadership')
            ->set('description', 'Original description')
            ->set('status', 'published')
            ->set('sortOrder', 3)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showProgrammeModal', false);

        $programme = Programme::firstOrFail();
        $slug = $programme->slug;

        $component->call('editProgramme', $programme->id)
            ->assertSet('showProgrammeModal', true)
            ->set('name', 'Discarded title')
            ->call('closeProgrammeModal')
            ->assertSet('showProgrammeModal', false);

        $this->assertSame('Editorial Leadership', $programme->fresh()->name);

        $component->call('editProgramme', $programme->id)
            ->set('description', 'Updated description')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($programme->id, $programme->fresh()->id);
        $this->assertSame($slug, $programme->fresh()->slug);
        $this->assertSame('Updated description', $programme->fresh()->description);
    }

    public function test_course_create_edit_and_cancel_preserve_permanent_identity(): void
    {
        $admin = User::factory()->academyAdmin()->create();
        $programme = Programme::factory()->create();

        $component = Livewire::actingAs($admin)->test(Courses::class)
            ->call('createCourse')
            ->assertSet('showCourseModal', true)
            ->set('title', 'Cross-cultural Reporting')
            ->set('programmeId', $programme->id)
            ->set('status', 'draft')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showCourseModal', false);

        $course = Course::firstOrFail();
        $slug = $course->slug;

        $component->call('editCourse', $course->id)
            ->set('title', 'Unsaved course title')
            ->call('closeCourseModal');

        $this->assertSame('Cross-cultural Reporting', $course->fresh()->title);

        $component->call('editCourse', $course->id)
            ->set('title', 'Cross-cultural Reporting Practice')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($slug, $course->fresh()->slug);
        $this->assertSame('Cross-cultural Reporting Practice', $course->fresh()->title);
    }

    public function test_every_saved_course_renders_its_permanent_checkout_url_when_ineligible(): void
    {
        $admin = User::factory()->academyAdmin()->create();
        $course = Course::factory()->draft()->create(['checkout_variant_id' => null]);

        $this->actingAs($admin)
            ->get(route('admin.courses'))
            ->assertOk()
            ->assertSee(route('checkout.course', ['course' => $course->slug]))
            ->assertSee('Checkout not ready')
            ->assertSee('Package selected')
            ->assertSee('Copy Checkout Link');
    }
}
