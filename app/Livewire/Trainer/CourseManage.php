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
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class CourseManage extends Component
{
    use WithFileUploads;

    public Course $course;

    public bool $showModuleModal = false;

    public ?int $editingModuleId = null;

    public string $moduleTitle = '';

    public string $moduleStatus = 'draft';

    public int $moduleSortOrder = 0;

    public bool $showLessonModal = false;

    public ?int $editingLessonId = null;

    public string $lessonTitle = '';

    public string $lessonContent = '';

    public bool $showContentPreview = false;

    public string $lessonType = 'text';

    public string $lessonStatus = 'draft';

    public bool $lessonRequired = true;

    public bool $requiresAcknowledgement = true;

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

    public string $materialVisibility = 'enrolled';

    public ?int $materialGroupId = null;

    public ?int $materialStudentId = null;

    public ?int $renamingId = null;

    public string $renameValue = '';

    public ?int $replacingId = null;

    public function mount(Course $course): void
    {
        $this->authorize('manageContent', $course);
    }

    public function createModule(): void
    {
        $this->resetModuleForm();
        $this->moduleSortOrder = (int) $this->course->modules()->max('sort_order') + 1;
        $this->showModuleModal = true;
    }

    public function editModule(int $moduleId): void
    {
        $this->resetModuleForm();
        $module = $this->course->modules()->findOrFail($moduleId);
        $this->editingModuleId = $module->id;
        $this->moduleTitle = $module->title;
        $this->moduleStatus = $module->status;
        $this->moduleSortOrder = $module->sort_order;
        $this->showModuleModal = true;
    }

    public function saveModule(): void
    {
        $data = $this->validate([
            'moduleTitle' => 'required|string|max:255',
            'moduleStatus' => 'required|in:draft,published',
            'moduleSortOrder' => 'required|integer|min:0',
        ]);

        $module = $this->editingModuleId
            ? $this->course->modules()->findOrFail($this->editingModuleId)
            : $this->course->modules()->make();
        $module->fill([
            'title' => $data['moduleTitle'],
            'sort_order' => $data['moduleSortOrder'],
            'status' => $data['moduleStatus'],
        ]);
        $module->save();

        session()->flash('saved', $this->editingModuleId ? 'Module updated.' : 'Module created.');
        $this->closeModuleModal();
    }

    public function closeModuleModal(): void
    {
        $this->showModuleModal = false;
        $this->resetModuleForm();
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
        $this->resetLessonForm();
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
            $this->requiresAcknowledgement = $l->requires_acknowledgement ?? true;
            $this->completionThreshold = $l->completion_threshold ?? 90;
            $this->videoSource = $l->video?->provider === 'upload' ? 'upload' : 'hosted';
            $this->videoId = (string) $l->video?->provider_video_id;
            $this->videoDurationSeconds = $l->video?->duration_seconds;
        }
        $this->editingLessonId = $lessonId;
        $this->showLessonModal = true;
    }

    public function saveLesson(FileService $files): void
    {
        $rules = [
            'lessonTitle' => 'required|string|max:255',
            'lessonType' => 'required|in:text,rich_text,pdf,video',
            'lessonStatus' => 'required|in:draft,published',
            'lessonRequired' => 'required|boolean',
            'requiresAcknowledgement' => 'required|boolean',
            'completionThreshold' => $this->lessonType === 'video' ? 'required|integer|min:1|max:100' : 'nullable',
            'lessonModuleId' => 'required|integer',
            'videoSource' => $this->lessonType === 'video' ? 'required|in:hosted,upload' : 'nullable',
            'videoId' => $this->lessonType === 'video' && $this->videoSource === 'hosted' ? 'required|string|max:500' : 'nullable',
            'videoDurationSeconds' => $this->lessonType === 'video' ? 'required|integer|min:1|max:86400' : 'nullable',
            'lessonUpload' => match (true) {
                $this->lessonType === 'pdf' => 'nullable|file|mimetypes:application/pdf|max:25600',
                $this->lessonType === 'video' && $this->videoSource === 'upload' => 'nullable|file|mimetypes:video/mp4,video/webm|max:524288',
                default => 'nullable',
            },
        ];
        $this->validate($rules);

        $module = $this->course->modules()->findOrFail($this->lessonModuleId);

        $lesson = $this->editingLessonId
            ? Lesson::whereKey($this->editingLessonId)
                ->whereHas('module', fn ($query) => $query->where('course_id', $this->course->id))
                ->firstOrFail()
            : new Lesson;

        if ($this->lessonType === 'video' && $this->videoSource === 'hosted') {
            if (! $this->isApprovedHostedVideo($this->videoId)) {
                throw ValidationException::withMessages([
                    'videoId' => 'Enter a valid YouTube or Vimeo video ID or URL.',
                ]);
            }
        }

        $hasCompatiblePrimary = $lesson->exists
            && $lesson->primaryMaterial !== null
            && ($this->lessonType === 'pdf'
                ? $lesson->primaryMaterial->mime === 'application/pdf'
                : in_array($lesson->primaryMaterial->mime, FileService::VIDEO_MIME, true));
        if (($this->lessonType === 'pdf' || ($this->lessonType === 'video' && $this->videoSource === 'upload'))
            && $this->lessonUpload === null && ! $hasCompatiblePrimary) {
            throw ValidationException::withMessages([
                'lessonUpload' => 'Upload the primary lesson file.',
            ]);
        }

        $lesson->fill([
            'course_module_id' => $module->id,
            'title' => $this->lessonTitle,
            'content' => $this->sanitiseRichText($this->lessonContent),
            'type' => $this->lessonType,
            'status' => $this->lessonStatus,
            'is_required' => $this->lessonRequired,
            'requires_acknowledgement' => $this->lessonType === 'pdf' ? $this->requiresAcknowledgement : true,
            'completion_threshold' => $this->lessonType === 'video' ? $this->completionThreshold : 90,
        ]);
        if (! $this->editingLessonId) {
            $lesson->sort_order = $module->lessons()->max('sort_order') + 1;
        }
        $lesson->save();

        if ($this->lessonUpload !== null) {
            $type = $this->lessonType === 'pdf' ? 'pdf' : 'video';
            $material = $lesson->primaryMaterial
                ? $files->replaceLessonContent($lesson->primaryMaterial, $this->lessonUpload, $type)
                : $files->storeLessonContent($this->lessonUpload, auth()->user(), $lesson, $type);
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

        session()->flash('saved', 'Lesson saved.');
        $this->closeLessonModal();
    }

    public function closeLessonModal(): void
    {
        $this->showLessonModal = false;
        $this->resetLessonForm();
    }

    public function updatedLessonType(): void
    {
        $this->lessonUpload = null;
        $this->resetValidation(['lessonUpload', 'videoId', 'videoDurationSeconds', 'completionThreshold']);
    }

    public function updatedVideoSource(): void
    {
        $this->lessonUpload = null;
        $this->resetValidation(['lessonUpload', 'videoId']);
    }

    #[Computed]
    public function lessonPreview(): string
    {
        return $this->lessonType === 'rich_text'
            ? $this->sanitiseRichText($this->lessonContent)
            : nl2br(e($this->lessonContent));
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
            'materialAudience' => 'required_if:materialVisibility,enrolled|in:course,group,student',
            'materialVisibility' => 'required|in:enrolled,trainers',
            'materialGroupId' => $this->materialVisibility === 'enrolled' && $this->materialAudience === 'group' ? 'required|integer' : 'nullable',
            'materialStudentId' => $this->materialVisibility === 'enrolled' && $this->materialAudience === 'student' ? 'required|integer' : 'nullable',
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
            $this->materialVisibility
        );
        $material->forceFill([
            'description' => $this->materialDescription ?: null,
            'group_id' => $this->materialVisibility === 'enrolled' && $this->materialAudience === 'group' ? $this->materialGroupId : null,
            'student_id' => $this->materialVisibility === 'enrolled' && $this->materialAudience === 'student' ? $this->materialStudentId : null,
        ])->save();

        $this->reset(['materialUpload', 'attachModuleId', 'attachLessonId', 'materialDescription', 'materialAudience', 'materialVisibility', 'materialGroupId', 'materialStudentId']);
        $this->materialAudience = 'course';
        $this->materialVisibility = 'enrolled';
        session()->flash('saved', 'Material uploaded.');
    }

    public function updatedMaterialVisibility(): void
    {
        $this->materialAudience = 'course';
        $this->materialGroupId = null;
        $this->materialStudentId = null;
        $this->resetValidation(['materialAudience', 'materialGroupId', 'materialStudentId']);
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
            'uploadLimitLabel' => $this->uploadLimitLabel(),
        ])->layout('layouts::app', ['title' => 'Manage: '.$this->course->title]);
    }

    private function resetModuleForm(): void
    {
        $this->reset(['editingModuleId', 'moduleTitle', 'moduleSortOrder']);
        $this->moduleStatus = 'draft';
        $this->resetValidation(['moduleTitle', 'moduleStatus', 'moduleSortOrder']);
    }

    private function resetLessonForm(): void
    {
        $this->reset([
            'editingLessonId', 'lessonTitle', 'lessonContent', 'showContentPreview', 'lessonType', 'lessonStatus',
            'lessonRequired', 'requiresAcknowledgement', 'completionThreshold', 'videoSource', 'videoId',
            'videoDurationSeconds', 'lessonUpload', 'lessonModuleId',
        ]);
        $this->lessonType = 'text';
        $this->lessonStatus = 'draft';
        $this->lessonRequired = true;
        $this->requiresAcknowledgement = true;
        $this->completionThreshold = 90;
        $this->videoSource = 'hosted';
        $this->resetValidation();
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

    private function uploadLimitLabel(): string
    {
        $uploadBytes = $this->iniBytes((string) ini_get('upload_max_filesize'));
        $postBytes = $this->iniBytes((string) ini_get('post_max_size'));
        $effectiveBytes = min(array_filter([$uploadBytes, $postBytes]));

        return $effectiveBytes >= 1024 * 1024
            ? number_format($effectiveBytes / (1024 * 1024), 0).' MB'
            : number_format($effectiveBytes / 1024, 0).' KB';
    }

    private function iniBytes(string $value): int
    {
        $number = (int) $value;

        return match (strtolower(substr(trim($value), -1))) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
