<?php

namespace App\Http\Controllers\Files;

use App\Domain\Files\FileService;
use App\Http\Controllers\Controller;
use App\Models\AssignmentAttachment;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrolment;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\TrainerAssignment;
use Illuminate\Support\Facades\Storage;

/**
 * Authorised file delivery. Files live on the private disk; access is checked
 * here — never via public URLs.
 */
class MaterialController extends Controller
{
    public function __construct(private FileService $files) {}

    public function show(Material $material)
    {
        $this->authorizeMaterial(request()->user(), $material);

        return $this->files->download($material);
    }

    public function attachment(AssignmentAttachment $attachment)
    {
        $submission = $attachment->submission;
        $this->authorize('view', $submission);

        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download(
            $attachment->path, $attachment->original_name, ['Content-Type' => $attachment->mime]
        );
    }

    /** Who may download a material: enrolled on its course, assigned trainer, or admin. */
    private function authorizeMaterial($user, Material $material): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $course = $this->resolveCourse($material);

        if ($user->isTrainer() && $course !== null) {
            $assigned = TrainerAssignment::where('trainer_id', $user->id)
                ->where('course_id', $course->id)->exists();
            abort_unless($assigned, 403);

            return;
        }

        // Student — must be enrolled on the owning course.
        abort_if($course === null, 403);
        $enrolled = Enrolment::where('user_id', $user->id)
            ->where('status', 'active')
            ->whereHas('variant', fn ($q) => $q->where('course_id', $course->id))
            ->exists();
        abort_unless($enrolled, 403);
    }

    private function resolveCourse(Material $material): ?Course
    {
        return match ($material->attachable_type) {
            Course::class => $material->attachable,
            CourseModule::class => $material->attachable?->course,
            Lesson::class => $material->attachable?->module?->course,
            default => null,
        };
    }
}
