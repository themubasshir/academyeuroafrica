<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\CourseVariant;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('catalogue:variants
    {--dry-run : Report only; writes nothing}')]
#[Description('Seed commercial CourseVariants from config/commercial-packages.php as draft+pending. Idempotent on source_key. Never publishes or enables purchases.')]
class CatalogueVariants extends Command
{
    public function handle(): int
    {
        $packages = config('commercial-packages');
        $dry = (bool) $this->option('dry-run');
        $log = [];

        DB::transaction(function () use ($packages, $dry, &$log) {
            foreach ($packages as $courseSlug => $variants) {
                $course = Course::where('slug', $courseSlug)->first();
                if (! $course) {
                    $log[] = "SKIP     course    {$courseSlug} (not imported)";

                    continue;
                }
                foreach ($variants as $v) {
                    $key = $courseSlug.'-'.$v['source_key'];
                    $variant = CourseVariant::firstOrNew(['source_key' => $key]);
                    $action = $variant->exists ? 'exists' : 'create';

                    // Never clobber a row an admin already reviewed/edited.
                    if ($variant->exists && $variant->approval_status !== 'pending') {
                        $action = 'locked';
                    } else {
                        $variant->fill([
                            'course_id' => $course->id,
                            'name' => $v['name'],
                            'delivery_type' => $v['delivery_type'],
                            'price_cents' => $v['price_cents'],
                            'currency' => 'EUR',
                            'duration' => $v['duration'] ?? null,
                            'inclusions' => $v['inclusions'] ?? null,
                            'price_source' => $v['price_source'] ?? null,
                            'checkout_ref' => $key,
                            // Force safe state — never publish, never approve here.
                            'status' => 'draft',
                            'approval_status' => 'pending',
                        ]);
                    }
                    $pend = isset($v['pending']) ? ' ⚠ '.$v['pending'] : '';
                    $price = $v['price_cents'] !== null
                        ? number_format($v['price_cents'] / 100, 2).' EUR'
                        : 'NO PRICE';
                    $log[] = strtoupper($action)."\tvariant\t{$key} [{$price}]{$pend}";
                    if (! $dry && $action !== 'locked') {
                        $variant->save();
                    }
                }
            }
            if ($dry) {
                DB::rollBack();
            }
        });

        foreach ($log as $l) {
            $this->line('  '.$l);
        }
        $this->info(($dry ? '[DRY RUN] ' : '').count($log).' variants processed.');
        $this->warn('All variants remain draft+pending. Nothing is purchasable.');

        return self::SUCCESS;
    }
}
