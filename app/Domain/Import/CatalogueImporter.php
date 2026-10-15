<?php

namespace App\Domain\Import;

use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\Programme;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Idempotent catalogue importer for the client source archive.
 *
 * Driven by a manifest array (see config/import-catalogue.php). Every row is
 * located by a stable key (programme/course slug, module/lesson source_key,
 * material source_key) so re-running neither duplicates nor overwrites
 * trainer-edited content. All writes run inside one transaction.
 */
class CatalogueImporter
{
    /** Per-run log lines surfaced by the console command. */
    public array $log = [];

    public function __construct(private DocxParser $docx = new DocxParser) {}

    /**
     * @param  array  $manifest  programmes → courses → {modules:[{file}|{lessons}], materials:[…]}
     * @param  bool  $dryRun  when true, validate + report only, never write
     */
    public function run(array $manifest, bool $dryRun = false): self
    {
        $this->log = [];

        return DB::transaction(function () use ($manifest, $dryRun) {
            foreach ($manifest['programmes'] as $prog) {
                $this->importProgramme($prog, $manifest['source_root'], $dryRun);
            }
            // On dry-run we never commit: wrap work and roll back at the end.
            if ($dryRun) {
                DB::rollBack();
            }

            return $this;
        });
    }

    private function importProgramme(array $prog, string $root, bool $dry): void
    {
        $slug = $prog['slug'];
        $programme = Programme::firstOrNew(['slug' => $slug]);
        $this->record($programme->exists ? 'exists' : 'create', 'programme', $slug);
        $programme->fill([
            'name' => $prog['name'],
            'description' => $prog['description'] ?? null,
            'status' => 'draft',
            'sort_order' => $prog['sort_order'] ?? 0,
        ]);
        if (! $dry) {
            $programme->save();
        }

        foreach ($prog['courses'] as $c) {
            $this->importCourse($programme, $c, $root, $dry);
        }
    }

    private function importCourse(Programme $programme, array $c, string $root, bool $dry): void
    {
        $slug = $c['slug'];
        $course = Course::firstOrNew(['slug' => $slug]);
        $this->record($course->exists ? 'exists' : 'create', 'course', $slug);
        $course->fill([
            'programme_id' => $programme->id,
            'title' => $c['title'],
            'description' => $c['description'] ?? null,
            'learning_outcomes' => $c['learning_outcomes'] ?? null,
            'delivery_types' => $c['delivery_types'] ?? ['live'],
            'status' => 'draft',
            'sort_order' => $c['sort_order'] ?? 0,
        ]);
        if (! $dry) {
            $course->save();
        }

        // Whole manual file → one row per "Module N …" section it contains.
        $modules = $c['modules'] ?? [];
        if (isset($c['manual_file'])) {
            $modules = array_merge($modules, $this->parseManualDocx($root.'/'.$c['manual_file']));
        }
        foreach ($modules as $i => $m) {
            $this->importModule($course, $m, $i + 1, $root, $c['slug'], $dry);
        }
        foreach (($c['materials'] ?? []) as $mat) {
            $this->attachMaterial($course, $mat, $root, $dry);
        }
        if (isset($c['manual_file'])) {
            $this->attachMaterial($course, [
                'file' => $c['manual_file'], 'source_key' => 'manual',
                'audience' => 'trainers', 'label' => $c['title'].' — full trainer manual',
            ], $root, $dry);
        }
    }

    private function importModule(Course $course, array $m, int $order, string $root, string $courseSlug, bool $dry): void
    {
        // Module may come from a parsed DOCX ('file') or an inline definition.
        $lessons = $m['lessons'] ?? [];
        if (isset($m['file'])) {
            $parsed = $this->parseModuleDocx($root.'/'.$m['file']);
            $m['title'] = $parsed['title'] ?? $m['title'];
            $lessons = $parsed['lessons'];
        }

        $key = $courseSlug.'-'.($m['source_key'] ?? 'm'.$order);
        $module = CourseModule::firstOrNew(['course_id' => $course->id, 'source_key' => $key]);
        $this->record($module->exists ? 'exists' : 'create', 'module', $key);
        $module->fill(['title' => $m['title'], 'sort_order' => $order, 'status' => 'draft']);
        if (! $dry) {
            $module->save();
        }

        foreach ($lessons as $j => $l) {
            $this->importLesson($course, $module, $l, $j + 1, $courseSlug, $dry);
        }
        if (isset($m['file'])) {
            // Whole source docx stays attached to the module as trainer-only.
            // Key becomes "<course>-<module-source_key>-src".
            $this->attachMaterial($module, [
                'file' => $m['file'], 'source_key' => ($m['source_key'] ?? 'm'.$order).'-src',
                'audience' => 'trainers', 'label' => $m['title'].' — trainer package',
            ], $root, $dry);
        }
    }

    private function importLesson(Course $course, CourseModule $module, array $l, int $order, string $courseSlug, bool $dry): void
    {
        $key = $courseSlug.'-'.($l['source_key'] ?? 'l'.$order);
        $lesson = Lesson::firstOrNew(['course_module_id' => $module->id, 'source_key' => $key]);
        $this->record($lesson->exists ? 'exists' : 'create', 'lesson', $key);
        $lesson->fill([
            'title' => $l['title'],
            'content' => $l['content'] ?? null,
            'type' => $l['type'] ?? 'text',
            'sort_order' => $order,
            'status' => 'draft',
        ]);
        if (! $dry) {
            $lesson->save();
        }

        if (! empty($l['quiz'])) {
            $this->importQuiz($course, $lesson, $l['quiz'], $key.'-q', $dry);
        }
    }

    /**
     * Parse a flagship module DOCX.
     *   H1 "MODULE N — TITLE"  → module title
     *   H1 "N.M — LESSON"      → lesson; H2 "<Letter>. <Section>" → subsections
     */
    private function parseModuleDocx(string $path): array
    {
        $paras = $this->docx->paragraphs($path);
        $title = null;
        $lessons = [];
        $cur = null;
        $curSection = null;

        foreach ($paras as $p) {
            $t = $p['text'];
            if ($p['level'] === 1) {
                if (preg_match('/^MODULE\s+(\d+)\s*[—-]\s*(.+)$/i', $t, $m)) {
                    $title = trim($m[2]);
                    $cur = null;
                } elseif (preg_match('/^(\d+)\.(\d+)\s*[—-]\s*(.+)$/', $t, $m)) {
                    $cur = ['title' => trim($m[3]), 'source_key' => "m$m[1]-l$m[2]", 'sections' => []];
                    $lessons[] = $cur;
                    $curSection = null;
                } else {
                    $cur = null;
                }

                continue;
            }
            if ($p['level'] === 2 && $cur !== null) {
                $curSection = trim(preg_replace('/^[A-Z]\.\s*/', '', $t));
                $lessons[array_key_last($lessons)]['sections'][$curSection] = [];

                continue;
            }
            if ($cur !== null && $curSection !== null) {
                $lessons[array_key_last($lessons)]['sections'][$curSection][] = $t;
            }
        }

        $built = [];
        foreach ($lessons as $l) {
            $sec = $l['sections'];
            $content = '';
            foreach ($sec as $name => $lines) {
                if (preg_match('/participant learning text/i', $name)) {
                    $content = implode("\n\n", $lines);
                }
            }
            $built[] = ['title' => $l['title'], 'source_key' => $l['source_key'],
                'content' => $content, 'quiz' => $this->buildQuiz($sec)];
        }

        return ['title' => $title, 'lessons' => $built];
    }

    /**
     * Parse a full trainer-manual DOCX into modules+lessons.
     *   H1 "Module N <Title>"   → module
     *   H2 "Lesson N.M <Title>" → lesson; following H3/Normal → content
     */
    private function parseManualDocx(string $path): array
    {
        $paras = $this->docx->paragraphs($path);
        $modules = [];
        $curMod = null;
        $curLesson = null;

        foreach ($paras as $p) {
            $t = $p['text'];
            if ($p['level'] === 1) {
                if (preg_match('/^Module\s+(\d+)\s+(.+)$/i', $t, $m)) {
                    $curMod = ['title' => trim($m[2]), 'module_no' => (int) $m[1],
                        'source_key' => 'm'.(int) $m[1], 'lessons' => []];
                    $modules[] = $curMod;
                    $curLesson = null;
                } else {
                    $curMod = null;
                    $curLesson = null;
                }

                continue;
            }
            if ($curMod !== null && $p['level'] === 2
                && preg_match('/^Lesson\s+(\d+)\.(\d+)\s+(.+)$/i', $t, $m)) {
                $curLesson = ['title' => trim($m[3]),
                    'source_key' => "m$m[1]-l$m[2]", 'body' => []];
                $modules[array_key_last($modules)]['lessons'][] = $curLesson;

                continue;
            }
            if ($curLesson !== null) {
                $mi = array_key_last($modules);
                $li = array_key_last($modules[$mi]['lessons']);
                $modules[$mi]['lessons'][$li]['body'][] = $t;
            }
        }

        foreach ($modules as &$mod) {
            foreach ($mod['lessons'] as &$l) {
                $l['content'] = implode("\n\n", $l['body']);
                unset($l['body']);
            }
        }

        return $modules;
    }

    /** Turn a lesson's quiz + answer-key sections into a quiz spec array. */
    private function buildQuiz(array $sections): ?array
    {
        $questions = [];
        $answers = [];
        foreach ($sections as $name => $lines) {
            if (preg_match('/lesson quiz|master quiz$/i', $name) && ! preg_match('/answer/i', $name)) {
                $questions = array_merge($questions, $lines);
            }
            if (preg_match('/answer key/i', $name)) {
                $answers = array_merge($answers, $lines);
            }
        }
        if (empty($questions)) {
            return null;
        }

        // Parse "N. <question>" + option lines "A. …" into items.
        $items = [];
        $cur = null;
        foreach ($questions as $ln) {
            if (preg_match('/^(\d+)\.\s*(.+)$/', $ln, $m)) {
                if ($cur) {
                    $items[] = $cur;
                }
                $cur = ['n' => (int) $m[1], 'text' => trim($m[2]), 'options' => []];
            } elseif ($cur && preg_match('/^([A-E])\.\s*(.+)$/', $ln, $m)) {
                $cur['options'][$m[1]] = trim($m[2]);
            }
        }
        if ($cur) {
            $items[] = $cur;
        }

        // Map answers "N. B — rationale".
        $ans = [];
        foreach ($answers as $ln) {
            if (preg_match('/^(\d+)\.\s*([A-E])\b/', $ln, $m)) {
                $ans[(int) $m[1]] = $m[2];
            }
        }
        foreach ($items as &$it) {
            $it['correct'] = $ans[$it['n']] ?? null;
        }

        return ['items' => $items];
    }

    private function importQuiz(Course $course, Lesson $lesson, array $quiz, string $key, bool $dry): void
    {
        // Quizzes hang off the course; the lesson title in the quiz title keeps
        // the link explicit. Idempotence is via the deterministic title.
        $title = $lesson->title.' — quiz';
        $q = Quiz::firstOrNew(['course_id' => $course->id, 'title' => $title]);
        $this->record($q->exists ? 'exists' : 'create', 'quiz', $key);
        $q->fill([
            'creator_id' => $this->adminId(),
            'title' => $title,
            'status' => 'draft',
        ]);
        if (! $dry) {
            $q->save();
        }
        $order = 0;
        foreach ($quiz['items'] as $it) {
            $order++;
            $question = QuizQuestion::firstOrNew(['quiz_id' => $q->id, 'sort_order' => $order]);
            $question->fill(['type' => 'mcq', 'prompt' => $it['text'], 'points' => 1, 'sort_order' => $order]);
            if (! $dry) {
                $question->save();
            }
            $optOrder = 0;
            foreach ($it['options'] as $letter => $text) {
                $optOrder++;
                $opt = QuizOption::firstOrNew(['quiz_question_id' => $question->id, 'sort_order' => $optOrder]);
                $opt->fill(['label' => $text, 'is_correct' => ($letter === $it['correct']), 'sort_order' => $optOrder]);
                if (! $dry) {
                    $opt->save();
                }
            }
        }
    }

    /** Copy a source file to private storage and attach it as a Material. */
    private function attachMaterial($attachable, array $mat, string $root, bool $dry): void
    {
        // Scope the key to the owning course so generic names
        // ("trainer-slides") don't collide across courses.
        $owner = $attachable instanceof Course ? $attachable->slug
            : ($attachable instanceof CourseModule ? $attachable->course?->slug : null);
        $key = ($owner ? $owner.'-' : '').$mat['source_key'];
        $existing = Material::where('source_key', $key)->first();
        $this->record($existing ? 'exists' : 'create', 'material', $key);
        if ($existing || $dry) {
            return;
        }

        $src = $root.'/'.$mat['file'];
        if (! is_file($src)) {
            $this->record('MISSING', 'material', $key.' ('.$mat['file'].')');

            return;
        }
        $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
        $path = 'import/'.Str::random(40).'.'.$ext;
        Storage::disk('local')->put($path, file_get_contents($src));

        Material::create([
            'uploader_id' => $mat['uploader_id'] ?? $this->adminId(),
            'attachable_type' => $attachable->getMorphClass(),
            'attachable_id' => $attachable->getKey(),
            'disk' => 'local', 'path' => $path,
            'original_name' => basename($mat['file']),
            'description' => $mat['label'] ?? basename($mat['file']),
            'mime' => mime_content_type($src) ?: 'application/octet-stream',
            'size_bytes' => filesize($src),
            'visibility' => $mat['audience'] === 'trainers' ? 'trainers' : 'enrolled',
            'source_key' => $key,
        ]);
    }

    /** First super-admin, else first user — owns imported quizzes/materials. */
    private function adminId(): int
    {
        return User::whereHas('roles', fn ($r) => $r->whereIn('name', ['super-admin', 'academy-admin']))
            ->value('id')
            ?? User::value('id')
            ?? 1;
    }

    private function record(string $action, string $type, string $key): void
    {
        $this->log[] = sprintf('%-8s %-9s %s', strtoupper($action), $type, $key);
    }
}
