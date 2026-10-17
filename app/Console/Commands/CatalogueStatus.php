<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\CourseVariant;
use App\Models\Material;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

#[Signature('academy:catalogue-status')]
#[Description('Report imported catalogue integrity, publication state, and private material availability.')]
class CatalogueStatus extends Command
{
    public function handle(): int
    {
        $materials = Material::query()->get(['id', 'disk', 'path', 'visibility', 'source_key']);
        $missing = $materials->filter(fn ($m) => ! Storage::disk($m->disk)->exists($m->path));

        $this->line(json_encode([
            'catalogue' => [
                'programmes' => DB::table('programmes')->count(),
                'courses' => DB::table('courses')->count(),
                'course_modules' => DB::table('course_modules')->count(),
                'lessons' => DB::table('lessons')->count(),
                'quizzes' => DB::table('quizzes')->count(),
                'materials' => $materials->count(),
            ],
            'publication' => [
                'courses' => Course::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
                'variants' => [
                    'total' => CourseVariant::count(),
                    'non_draft' => CourseVariant::where('status', '<>', 'draft')->count(),
                    'non_pending' => CourseVariant::where('approval_status', '<>', 'pending')->count(),
                    'purchasable_state' => CourseVariant::where('status', 'published')
                        ->where('approval_status', 'approved')->whereNotNull('price_cents')->count(),
                ],
            ],
            'materials' => [
                'missing_storage' => $missing->count(),
                'missing_source_keys' => $missing->pluck('source_key')->values(),
                'visibility' => $materials->groupBy('visibility')->map->count(),
            ],
            'queue' => [
                'jobs' => DB::table('jobs')->count(),
                'failed_jobs' => DB::table('failed_jobs')->count(),
            ],
        ], JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
