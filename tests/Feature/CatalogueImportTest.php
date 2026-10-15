<?php

namespace Tests\Feature;

use App\Domain\Import\CatalogueImporter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogueImportTest extends TestCase
{
    use RefreshDatabase;

    /** Minimal manifest using a generated DOCX so no real files are needed. */
    private function manifest(string $root): array
    {
        return [
            'source_root' => $root,
            'programmes' => [[
                'slug' => 'prog', 'name' => 'Prog', 'sort_order' => 1,
                'courses' => [[
                    'slug' => 'course-one', 'title' => 'Course One',
                    'delivery_types' => ['live'], 'sort_order' => 1,
                    'modules' => [[
                        'source_key' => 'm1', 'title' => 'Module 1',
                        'lessons' => [
                            ['title' => 'L1', 'content' => 'Body 1'],
                            ['title' => 'L2', 'content' => 'Body 2'],
                        ],
                    ]],
                    'materials' => [[
                        'file' => 'manual.docx', 'source_key' => 'manual',
                        'audience' => 'trainers', 'label' => 'Manual',
                    ]],
                ]],
            ]],
        ];
    }

    private function fakeSource(): string
    {
        $dir = sys_get_temp_dir().'/imp-'.uniqid();
        mkdir($dir, 0777, true);
        file_put_contents($dir.'/manual.docx', 'fake-docx-bytes');

        return $dir;
    }

    public function test_imports_catalogue_as_drafts_and_is_idempotent(): void
    {
        User::factory()->create();
        $root = $this->fakeSource();
        $importer = new CatalogueImporter;

        $importer->run($this->manifest($root));
        $course = Course::where('slug', 'course-one')->first();

        $this->assertSame('draft', $course->status);
        $this->assertCount(1, $course->modules);
        $this->assertCount(2, $course->modules->first()->lessons);
        $this->assertEquals(0, $course->variants()->count()); // no invented prices

        // Second run must not duplicate anything.
        $importer->run($this->manifest($root));
        $this->assertEquals(1, Course::where('slug', 'course-one')->count());
        $this->assertEquals(2, Lesson::count());
        $this->assertEquals(1, Material::count());
    }

    public function test_dry_run_writes_nothing(): void
    {
        $root = $this->fakeSource();
        (new CatalogueImporter)->run($this->manifest($root), dryRun: true);

        $this->assertEquals(0, Course::count());
        $this->assertEquals(0, Material::count());
    }

    public function test_trainer_material_hidden_from_students(): void
    {
        User::factory()->create();
        $root = $this->fakeSource();
        (new CatalogueImporter)->run($this->manifest($root));

        $material = Material::where('visibility', 'trainers')->firstOrFail();
        $student = User::factory()->create();
        $trainer = User::factory()->create();
        $trainer->assignRole('trainer');

        $this->assertFalse($material->visibleTo($student));
        $this->assertTrue($material->visibleTo($trainer));
        $this->assertTrue(Storage::disk($material->disk)->exists($material->path));
    }
}
