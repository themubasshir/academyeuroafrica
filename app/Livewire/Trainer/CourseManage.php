<?php

namespace App\Livewire\Trainer;

use App\Domain\Files\FileService;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
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

    public ?int $attachModuleId = null;

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
        $this->validate(['materialUpload' => 'required|file|max:25600', 'attachModuleId' => 'nullable|integer']);
        $attachable = $this->attachModuleId
            ? $this->course->modules()->findOrFail($this->attachModuleId)
            : $this->course;

        $files->storeMaterial($this->materialUpload, auth()->user(), $attachable);
        $this->reset(['materialUpload', 'attachModuleId']);
        session()->flash('saved', 'Material uploaded.');
    }

    public function render()
    {
        return view('livewire.trainer.course-manage', [
            'modules' => $this->course->modules()->with('lessons.video')->orderBy('sort_order')->get(),
            'materials' => $this->course->materials()->latest()->get(),
        ])->layout('layouts::app', ['title' => 'Manage: '.$this->course->title]);
    }
}
