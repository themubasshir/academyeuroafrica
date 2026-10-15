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
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class CourseManage extends Component
{
    use WithFileUploads;

    public Course $course;

    // module/lesson form state
    public string $moduleTitle = '';

    public ?int $editingLessonId = null;

    public string $lessonTitle = '';

    public string $lessonContent = '';

    public string $lessonType = 'text';

    public string $videoId = '';

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
        $this->validate(['moduleTitle' => 'required|string|max:255']);
        $this->course->modules()->create([
            'title' => $this->moduleTitle,
            'sort_order' => $this->course->modules()->max('sort_order') + 1,
            'status' => 'published',
        ]);
        $this->reset('moduleTitle');
    }

    public function deleteModule(int $id): void
    {
        $this->course->modules()->where('id', $id)->delete();
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
        $this->attachModuleId = $moduleId;
        if ($lessonId) {
            $l = Lesson::findOrFail($lessonId);
            $this->lessonTitle = $l->title;
            $this->lessonContent = (string) $l->content;
            $this->lessonType = $l->type;
            $this->videoId = (string) $l->video?->provider_video_id;
        } else {
            $this->reset(['lessonTitle', 'lessonContent', 'lessonType', 'videoId']);
        }
    }

    public function saveLesson(): void
    {
        $this->validate([
            'lessonTitle' => 'required|string|max:255',
            'lessonType' => 'required|in:text,video',
            'videoId' => 'required_if:lessonType,video|nullable|string|max:191',
        ]);

        $module = $this->course->modules()->findOrFail($this->attachModuleId);

        $lesson = $this->editingLessonId
            ? Lesson::findOrFail($this->editingLessonId)
            : new Lesson;

        $lesson->fill([
            'course_module_id' => $module->id,
            'title' => $this->lessonTitle,
            'content' => $this->lessonContent,
            'type' => $this->lessonType,
            'status' => 'published',
        ]);
        if (! $this->editingLessonId) {
            $lesson->sort_order = $module->lessons()->max('sort_order') + 1;
        }
        $lesson->save();

        if ($this->lessonType === 'video' && $this->videoId) {
            $lesson->video()->updateOrCreate(
                ['lesson_id' => $lesson->id],
                ['provider' => 'generic', 'provider_video_id' => $this->videoId, 'title' => $this->lessonTitle, 'status' => 'published']
            );
        }

        $this->reset(['editingLessonId', 'lessonTitle', 'lessonContent', 'lessonType', 'videoId', 'attachModuleId']);
    }

    public function deleteLesson(int $id): void
    {
        Lesson::where('id', $id)->whereHas('module', fn ($q) => $q->where('course_id', $this->course->id))->delete();
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
        $this->validate(['materialReplace' => 'required|file|max:25600']);
        $m = $this->courseMaterials()->findOrFail($this->replacingId);
        Storage::disk($m->disk)->delete($m->path);
        $m->forceFill([
            'path' => 'materials/'.Str::random(40).'.'.($this->materialReplace->guessExtension() ?: 'bin'),
            'mime' => $this->materialReplace->getMimeType(),
            'size_bytes' => $this->materialReplace->getSize(),
            'original_name' => $this->materialReplace->getClientOriginalName(),
        ])->save();
        Storage::disk($m->disk)->put($m->path, $this->materialReplace->getContent());
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
            'modules' => $this->course->modules()->with('lessons.video')->orderBy('sort_order')->get(),
            'materials' => $this->courseMaterials()->with('group', 'student')->latest()->get(),
            'groups' => Group::where('course_id', $this->course->id)->get(),
            'students' => Enrolment::where('status', 'active')
                ->whereHas('variant', fn ($q) => $q->where('course_id', $this->course->id))
                ->with('user')->get()->pluck('user')->filter()->sortBy('name')->values(),
        ])->layout('layouts::app', ['title' => 'Manage: '.$this->course->title]);
    }
}
