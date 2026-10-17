<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('academy:deploy-status')]
#[Description('Report private deployment diagnostics: catalogue row and queue counts.')]
class DeployStatus extends Command
{
    public function handle(): int
    {
        $this->line(json_encode([
            'catalogue' => [
                'programmes' => DB::table('programmes')->count(),
                'courses' => DB::table('courses')->count(),
                'course_modules' => DB::table('course_modules')->count(),
                'lessons' => DB::table('lessons')->count(),
                'quizzes' => DB::table('quizzes')->count(),
                'materials' => DB::table('materials')->count(),
            ],
            'queue' => [
                'jobs' => DB::table('jobs')->count(),
                'failed_jobs' => DB::table('failed_jobs')->count(),
            ],
        ], JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
