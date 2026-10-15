<?php

namespace Tests\Feature;

use App\Domain\Files\FileService;
use App\Models\Course;
use App\Models\CourseVariant;
use App\Models\Enrolment;
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
}
