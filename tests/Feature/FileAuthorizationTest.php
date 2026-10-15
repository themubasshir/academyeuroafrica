<?php

namespace Tests\Feature;

use App\Domain\Files\FileService;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseVariant;
use App\Models\Enrolment;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class FileAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function materialOnCourse(Course $course): Material
    {
        Storage::fake('local');
        Storage::disk('local')->put('materials/x.pdf', '%PDF-1.4 test');

        return Material::create([
            'uploader_id' => User::factory()->trainer()->create()->id,
            'attachable_type' => Course::class,
            'attachable_id' => $course->id,
            'disk' => 'local',
            'path' => 'materials/x.pdf',
            'original_name' => 'x.pdf',
            'mime' => 'application/pdf',
            'size_bytes' => 100,
            'visibility' => 'enrolled',
        ]);
    }

    public function test_enrolled_student_downloads_material(): void
    {
        $course = Course::factory()->create();
        $material = $this->materialOnCourse($course);
        $student = User::factory()->student()->create();
        Enrolment::factory()->create([
            'user_id' => $student->id,
            'course_variant_id' => CourseVariant::factory()->for($course)->create()->id,
        ]);

        $this->actingAs($student)->get(route('files.material', $material))->assertOk();
    }

    public function test_non_enrolled_student_denied(): void
    {
        $course = Course::factory()->create();
        $material = $this->materialOnCourse($course);
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('files.material', $material))->assertForbidden();
    }

    public function test_guest_denied(): void
    {
        $material = $this->materialOnCourse(Course::factory()->create());
        $this->get(route('files.material', $material))->assertRedirect(route('login'));
    }

    public function test_disallowed_mime_rejected_on_upload(): void
    {
        Storage::fake('local');
        $files = new FileService;
        $exe = UploadedFile::fake()->create('evil.exe', 10, 'application/x-msdownload');

        $this->expectException(HttpException::class);
        $files->storeMaterial($exe, User::factory()->trainer()->create(), Course::factory()->create());
    }

    private function enrolledStudent(Course $course): User
    {
        $variant = $course->variants()->first()
            ?? CourseVariant::factory()->for($course)->create();
        $student = User::factory()->student()->create();
        Enrolment::firstOrCreate(
            ['user_id' => $student->id, 'course_variant_id' => $variant->id],
            ['status' => 'active', 'enrolled_at' => now()]
        );

        return $student;
    }

    public function test_material_on_module_resolves_course_and_allows_enrolled(): void
    {
        $course = Course::factory()->create();
        $module = CourseModule::create(['course_id' => $course->id, 'title' => 'M1', 'sort_order' => 1, 'status' => 'published']);
        Storage::fake('local');
        Storage::disk('local')->put('materials/m.pdf', 'x');
        $material = Material::create([
            'uploader_id' => User::factory()->trainer()->create()->id,
            'attachable_type' => CourseModule::class, 'attachable_id' => $module->id,
            'disk' => 'local', 'path' => 'materials/m.pdf', 'original_name' => 'm.pdf',
            'mime' => 'application/pdf', 'size_bytes' => 10, 'visibility' => 'enrolled',
        ]);

        $this->actingAs($this->enrolledStudent($course))
            ->get(route('files.material', $material))->assertOk();
    }

    public function test_material_on_lesson_resolves_course(): void
    {
        $course = Course::factory()->create();
        $module = CourseModule::create(['course_id' => $course->id, 'title' => 'M1', 'sort_order' => 1, 'status' => 'published']);
        $lesson = Lesson::create(['course_module_id' => $module->id, 'title' => 'L1', 'type' => 'text', 'sort_order' => 1, 'status' => 'published']);
        Storage::fake('local');
        Storage::disk('local')->put('materials/l.pdf', 'x');
        $material = Material::create([
            'uploader_id' => User::factory()->trainer()->create()->id,
            'attachable_type' => Lesson::class, 'attachable_id' => $lesson->id,
            'disk' => 'local', 'path' => 'materials/l.pdf', 'original_name' => 'l.pdf',
            'mime' => 'application/pdf', 'size_bytes' => 10, 'visibility' => 'enrolled',
        ]);

        $this->actingAs($this->enrolledStudent($course))
            ->get(route('files.material', $material))->assertOk();
    }

    public function test_student_enrolled_on_other_course_denied(): void
    {
        $courseA = Course::factory()->create();
        $courseB = Course::factory()->create();
        $material = $this->materialOnCourse($courseA);
        $student = $this->enrolledStudent($courseB); // enrolled on B, material on A

        $this->actingAs($student)->get(route('files.material', $material))->assertForbidden();
    }

    public function test_group_material_denied_to_non_member_enrolled(): void
    {
        $course = Course::factory()->create();
        $group = Group::create(['course_id' => $course->id, 'name' => 'Cohort X', 'trainer_id' => User::factory()->trainer()->create()->id]);
        $material = $this->materialOnCourse($course);
        $material->forceFill(['group_id' => $group->id])->save();

        // Enrolled on the course but NOT in the group → denied.
        $outsider = $this->enrolledStudent($course);
        $this->actingAs($outsider)->get(route('files.material', $material))->assertForbidden();

        // Member of the group → allowed.
        $member = $this->enrolledStudent($course);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $member->id]);
        $this->actingAs($member)->get(route('files.material', $material))->assertOk();
    }

    public function test_student_targeted_material_only_for_that_student(): void
    {
        $course = Course::factory()->create();
        $material = $this->materialOnCourse($course);
        $target = $this->enrolledStudent($course);
        $material->forceFill(['student_id' => $target->id])->save();

        $other = $this->enrolledStudent($course); // enrolled, but not the target
        $this->actingAs($other)->get(route('files.material', $material))->assertForbidden();
        $this->actingAs($target)->get(route('files.material', $material))->assertOk();
    }
}
