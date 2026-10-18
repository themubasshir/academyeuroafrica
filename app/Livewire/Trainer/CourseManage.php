<?php

namespace App\Livewire\Trainer;

use App\Domain\Files\FileService;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrolment;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\Material;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class CourseManage extends Component
{
    use WithFileUploads;

    public Course $course;

    // module/lesson form state
    public string $moduleTitle = '';

    public string $moduleStatus = 'draft';

    public ?int $editingLessonId = null;

    public string $lessonTitle = '';

    public string $lessonContent = '';

    public string $lessonType = 'text';

    public string $lessonStatus = 'draft';

    public bool $lessonRequired = true;

    public int $completionThreshold = 90;

    public ?int $lessonModuleId = null;

    public string $videoSource = 'hosted';

    public string $videoId = '';

    public ?int $videoDurationSeconds = null;

    public $lessonUpload = null;

    public $materialUpload;

    public $materialReplace = null;

    public ?int $attachModuleId = null;

    public ?int $attachLessonId = null;

    public string $materialDescription = '';

    public string $materialAudience = 'course'; // course | group | student

    public ?int $materialGroupId = null;

    public ?int $materialStudentId = null;

    public ?int $renamingId = null;

    public string $renameValue = '';

    public ?int $replacingId = null;

    public function mount(Course $course): void
    {
        $this->authorize('manageContent', $course);
    }

    public function saveModule(): void
    {
        $this->validate([
            'moduleTitle' => 'required|string|max:255',
            'moduleStatus' => 'required|in:draft,published',
        ]);
        $this->course->modules()->create([
            'title' => $this->moduleTitle,
            'sort_order' => $this->course->modules()->max('sort_order') + 1,
            'status' => $this->moduleStatus,
        ]);
        $this->reset('moduleTitle');
        $this->moduleStatus = 'draft';
    }

    public function deleteModule(int $id): void
    {
        $module = $this->course->modules()->with('lessons.materials')->findOrFail($id);
        foreach ($module->lessons as $lesson) {
            $this->deleteLessonFiles($lesson);
        }
        $module->delete();
    }

    public function moveModule(int $id, string $dir): void
    {
        $modules = $this->course->modules()->orderBy('sort_order')->pluck('id')->values();
        $i = $modules->search($id);
        $swap = $dir === 'up' ? $modules->get($i - 1) : $modules->get($i + 1);
        if ($i === false || $swap === null) {
            return;
        }

        $a = CourseModule::find($id);
        $b = CourseModule::find($swap);
        [$a->sort_order, $b->sort_order] = [$b->sort_order, $a->sort_order];
        $a->save();
        $b->save();
    }

    public function editLesson(?int $lessonId, int $moduleId): void
    {
        $this->editingLessonId = $lessonId;
        $this->lessonModuleId = $moduleId;
        if ($lessonId) {
            $l = Lesson::whereKey($lessonId)
                ->whereHas('module', fn ($query) => $query->where('course_id', $this->course->id))
                ->with(['video', 'primaryMaterial'])
                ->firstOrFail();
            $this->lessonTitle = $l->title;
            $this->lessonContent = (string) $l->content;
            $this->lessonType = $l->type;
            $this->lessonStatus = $l->status;
            $this->lessonRequired = $l->is_required ?? true;
            $this->completionThreshold = $l->completion_threshold ?? 90;
            $this->videoSource = $l->video?->provider === 'upload' ? 'upload' : 'hosted';
            $this->videoId = (string) $l->video?->provider_video_id;
            $this->videoDurationSeconds = $l->video?->duration_seconds;
        } else {
            $this->resetLessonForm(keepModule: true);
        }
    }

    public function saveLesson(FileService $files): void
    {
        $this->validate([
            'lessonTitle' => 'required|string|max:255',
            'lessonType' => 'required|in:text,rich_text,pdf,video',
            'lessonStatus' => 'required|in:draft,published',
            'lessonRequired' => 'required|boolean',
            'completionThreshold' => 'required|integer|min:1|max:100',
            'lessonModuleId' => 'required|integer',
            'videoSource' => 'required_if:lessonType,video|in:hosted,upload',
            'videoId' => 'nullable|string|max:500',
            'videoDurationSeconds' => 'required_if:lessonType,video|nullable|integer|min:1|max:86400',
            'lessonUpload' => 'nullable|file|max:524288',
        ]);

        $module = $this->course->modules()->findOrFail($this->lessonModuleId);

        $lesson = $this->editingLessonId
            ? Lesson::whereKey($this->editingLessonId)
                ->whereHas('module', fn ($query) => $query->where('course_id', $this->course->id))
                ->firstOrFail()
            : new Lesson;

        if ($this->lessonType === 'video' && $this->videoSource === 'hosted') {
            abort_if(blank($this->videoId), 422, 'Enter a hosted video ID or URL.');
            abort_unless($this->isApprovedHostedVideo($this->videoId), 422, 'Use a YouTube or Vimeo video ID or URL.');
        }

        $hasCompatiblePrimary = $lesson->exists
            && $lesson->primaryMaterial !== null
            && ($this->lessonType === 'pdf'
                ? $lesson->primaryMaterial->mime === 'application/pdf'
                : in_array($lesson->primaryMaterial->mime, FileService::VIDEO_MIME, true));
        if (($this->lessonType === 'pdf' || ($this->lessonType === 'video' && $this->videoSource === 'upload'))
            && $this->lessonUpload === null && ! $hasCompatiblePrimary) {
            abort(422, 'Upload the primary lesson file.');
        }

        $lesson->fill([
            'course_module_id' => $module->id,
            'title' => $this->lessonTitle,
            'content' => $this->sanitiseRichText($this->lessonContent),
            'type' => $this->lessonType,
            'status' => $this->lessonStatus,
            'is_required' => $this->lessonRequired,
            'completion_threshold' => $this->completionThreshold,
        ]);
        if (! $this->editingLessonId) {
            $lesson->sort_order = $module->lessons()->max('sort_order') + 1;
        }
        $lesson->save();

        if ($this->lessonUpload !== null) {
            $material = $files->storeLessonContent(
                $this->lessonUpload,
                auth()->user(),
                $lesson,
                $this->lessonType === 'pdf' ? 'pdf' : 'video',
            );
            $lesson->forceFill(['primary_material_id' => $material->id])->save();
        } elseif (! in_array($this->lessonType, ['pdf', 'video'], true)
            || ($this->lessonType === 'video' && $this->videoSource === 'hosted')) {
            $lesson->forceFill(['primary_material_id' => null])->save();
        }

        if ($this->lessonType === 'video') {
            $providerVideoId = $this->videoSource === 'upload'
                ? (string) $lesson->primary_material_id
                : trim($this->videoId);
            $lesson->video()->updateOrCreate(
                ['lesson_id' => $lesson->id],
                [
                    'provider' => $this->videoSource === 'upload' ? 'upload' : 'generic',
                    'provider_video_id' => $providerVideoId,
                    'title' => $this->lessonTitle,
                    'duration_seconds' => $this->videoDurationSeconds,
                    'status' => $this->lessonStatus,
                ]
            );
        } else {
            $lesson->video()->delete();
        }

        $this->resetLessonForm();
        session()->flash('saved', 'Lesson saved.');
    }

    public function moveLesson(int $id, string $direction): void
    {
        $lesson = Lesson::whereKey($id)
            ->whereHas('module', fn ($query) => $query->where('course_id', $this->course->id))
            ->firstOrFail();
        $ids = $lesson->module->lessons()->orderBy('sort_order')->orderBy('id')->pluck('id')->values();
        $index = $ids->search($lesson->id);
        $swapId = $direction === 'up' ? $ids->get($index - 1) : $ids->get($index + 1);

        if ($index === false || $swapId === null) {
            return;
        }

        $swap = Lesson::findOrFail($swapId);
        [$lesson->sort_order, $swap->sort_order] = [$swap->sort_order, $lesson->sort_order];
        $lesson->save();
        $swap->save();
    }

    public function deleteLesson(int $id): void
    {
        $lesson = Lesson::where('id', $id)
            ->whereHas('module', fn ($q) => $q->where('course_id', $this->course->id))
            ->with('materials')
            ->firstOrFail();
        $this->deleteLessonFiles($lesson);
        $lesson->delete();
    }

    public function uploadMaterial(FileService $files): void
    {
        $this->validate([
            'materialUpload' => 'required|file|max:25600',
            'attachModuleId' => 'nullable|integer',
            'attachLessonId' => 'nullable|integer',
            'materialDescription' => 'nullable|string|max:255',
            'materialAudience' => 'required|in:course,group,student',
            'materialGroupId' => 'required_if:materialAudience,group|nullable|integer',
            'materialStudentId' => 'required_if:materialAudience,student|nullable|integer',
        ]);

        $attachable = $this->attachLessonId
            ? Lesson::where('id', $this->attachLessonId)->whereHas('module', fn ($q) => $q->where('course_id', $this->course->id))->firstOrFail()
            : ($this->attachModuleId
                ? $this->course->modules()->findOrFail($this->attachModuleId)
                : $this->course);

        $material = $files->storeMaterial(
            $this->materialUpload,
            auth()->user(),
            $attachable,
            'enrolled'
        );
        $material->forceFill([
            'description' => $this->materialDescription ?: null,
            'group_id' => $this->materialAudience === 'group' ? $this->materialGroupId : null,
            'student_id' => $this->materialAudience === 'student' ? $this->materialStudentId : null,
        ])->save();

        $this->reset(['materialUpload', 'attachModuleId', 'attachLessonId', 'materialDescription', 'materialAudience', 'materialGroupId', 'materialStudentId']);
        session()->flash('saved', 'Material uploaded.');
    }

    /** Rename = change display name/description, not the stored path. */
    public function startRename(int $id): void
    {
        $m = $this->courseMaterials()->findOrFail($id);
        $this->renamingId = $id;
        $this->renameValue = $m->description ?: $m->original_name;
    }

    public function saveRename(): void
    {
        $this->validate(['renameValue' => 'required|string|max:255']);
        $m = $this->courseMaterials()->findOrFail($this->renamingId);
        $m->forceFill(['description' => $this->renameValue])->save();
        $this->reset(['renamingId', 'renameValue']);
        session()->flash('saved', 'Material renamed.');
    }

    /** Replace the file body, keeping the record (and its audience) intact. */
    public function replaceMaterial(FileService $files): void
    {
        $this->validate(['materialReplace' => 'required|file|max:524288']);
        $m = $this->courseMaterials()->findOrFail($this->replacingId);
        $files->replaceMaterial($m, $this->materialReplace);
        $this->reset(['materialReplace', 'replacingId']);
        session()->flash('saved', 'File replaced.');
    }

    public function deleteMaterial(int $id): void
    {
        $m = $this->courseMaterials()->findOrFail($id);
        Storage::disk($m->disk)->delete($m->path);
        $m->delete();
        session()->flash('saved', 'Material deleted.');
    }

    /** Materials on this course's whole tree — course, its modules, its lessons. */
    private function courseMaterials()
    {
        $moduleIds = $this->course->modules()->pluck('id');
        $lessonIds = Lesson::whereIn('course_module_id', $moduleIds)->pluck('id');

        return Material::where(fn ($q) => $q
            ->where(fn ($x) => $x->where('attachable_type', Course::class)->where('attachable_id', $this->course->id))
            ->orWhere(fn ($x) => $x->where('attachable_type', CourseModule::class)->whereIn('attachable_id', $moduleIds))
            ->orWhere(fn ($x) => $x->where('attachable_type', Lesson::class)->whereIn('attachable_id', $lessonIds))
        );
    }

    public function render()
    {
        return view('livewire.trainer.course-manage', [
            'modules' => $this->course->modules()->with(['lessons.video', 'lessons.primaryMaterial'])->orderBy('sort_order')->get(),
            'materials' => $this->courseMaterials()->with('group', 'student')->latest()->get(),
            'groups' => Group::where('course_id', $this->course->id)->get(),
            'students' => Enrolment::where('status', 'active')
                ->whereHas('variant', fn ($q) => $q->where('course_id', $this->course->id))
                ->with('user')->get()->pluck('user')->filter()->sortBy('name')->values(),
        ])->layout('layouts::app', ['title' => 'Manage: '.$this->course->title]);
    }

    private function resetLessonForm(bool $keepModule = false): void
    {
        $moduleId = $this->lessonModuleId;
        $this->reset([
            'editingLessonId', 'lessonTitle', 'lessonContent', 'lessonType', 'lessonStatus',
            'lessonRequired', 'completionThreshold', 'videoSource', 'videoId',
            'videoDurationSeconds', 'lessonUpload', 'lessonModuleId',
        ]);
        $this->lessonType = 'text';
        $this->lessonStatus = 'draft';
        $this->lessonRequired = true;
        $this->completionThreshold = 90;
        $this->videoSource = 'hosted';
        if ($keepModule) {
            $this->lessonModuleId = $moduleId;
        }
    }

    private function sanitiseRichText(string $content): string
    {
        $content = strip_tags($content, '<p><br><strong><em><ul><ol><li><h2><h3><blockquote><code>');

        return (string) preg_replace('/<([a-z][a-z0-9]*)\b[^>]*>/i', '<$1>', $content);
    }

    private function isApprovedHostedVideo(string $value): bool
    {
        $value = trim($value);
        if (preg_match('/^[\w-]{11}$/', $value) === 1 || ctype_digit($value)) {
            return true;
        }

        $host = strtolower((string) parse_url($value, PHP_URL_HOST));

        return in_array($host, [
            'youtube.com', 'www.youtube.com', 'youtu.be', 'www.youtu.be',
            'youtube-nocookie.com', 'www.youtube-nocookie.com',
            'vimeo.com', 'www.vimeo.com', 'player.vimeo.com',
        ], true);
    }

    private function deleteLessonFiles(Lesson $lesson): void
    {
        foreach ($lesson->materials as $material) {
            Storage::disk($material->disk)->delete($material->path);
            $material->delete();
        }
    }
}
