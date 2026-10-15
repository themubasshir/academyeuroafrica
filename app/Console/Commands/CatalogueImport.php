<?php

namespace App\Console\Commands;

use App\Domain\Import\CatalogueImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

#[Signature('catalogue:import
    {--dry-run : Validate and report only; writes nothing}
    {--source= : Override the source_root (extracted archive dir)}
    {--manifest= : Path to a JSON manifest override}')]
#[Description('Import the client course catalogue (programmes, courses, modules, lessons, quizzes, materials) as unpublished drafts. Idempotent.')]
class CatalogueImport extends Command
{
    public function handle(CatalogueImporter $importer): int
    {
        $manifest = $this->loadManifest();
        if ($manifest === null) {
            return self::FAILURE;
        }

        if ($src = $this->option('source')) {
            $manifest['source_root'] = $src;
        }

        $root = $manifest['source_root'] ?? null;
        if (! $root || ! is_dir($root)) {
            $this->error("source_root not found: {$root}");
            $this->line('Set IMPORT_SOURCE_ROOT in .env or pass --source=/path/to/extracted-archive');

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $this->info(($dry ? '[DRY RUN] ' : '').'Importing catalogue from '.$root);

        try {
            $importer->run($manifest, $dry);
        } catch (\Throwable $e) {
            $this->error('Import failed: '.$e->getMessage());

            return self::FAILURE;
        }

        foreach ($importer->log as $line) {
            $this->line('  '.$line);
        }

        $counts = array_count_values(array_map(
            fn ($l) => strtok($l, ' ').' '.strtok(' '), $importer->log));
        $this->newLine();
        $this->info(($dry ? '[DRY RUN] would have processed ' : 'Processed ')
            .count($importer->log).' rows.');
        foreach (['programme', 'course', 'module', 'lesson', 'quiz', 'material'] as $t) {
            $created = $counts["CREATE $t"] ?? 0;
            $existed = $counts["EXISTS $t"] ?? 0;
            $missing = $counts["MISSING $t"] ?? 0;
            if ($created || $existed || $missing) {
                $this->line(sprintf('   %-9s created:%d  existing:%d%s',
                    $t, $created, $existed, $missing ? "  <error>missing:$missing</error>" : ''));
            }
        }

        return self::SUCCESS;
    }

    /** @return array<string,mixed>|null */
    private function loadManifest(): ?array
    {
        if ($file = $this->option('manifest')) {
            $data = json_decode(File::get($file), true);
            if (! is_array($data)) {
                $this->error("Invalid JSON manifest: {$file}");

                return null;
            }

            return $data;
        }

        return config('import-catalogue');
    }
}
