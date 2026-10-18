<?php

namespace App\Http\Controllers\Files;

use App\Domain\Files\FileService;
use App\Http\Controllers\Controller;
use App\Models\AssignmentAttachment;
use App\Models\Course;
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

    public function inline(Material $material)
    {
        $this->authorizeMaterial(request()->user(), $material);
        abort_unless($material->mime === 'application/pdf', 404);
        abort_unless(Storage::disk($material->disk)->exists($material->path), 404);

        return response()->file(Storage::disk($material->disk)->path($material->path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function stream(Material $material)
    {
        $this->authorizeMaterial(request()->user(), $material);
        abort_unless(in_array($material->mime, FileService::VIDEO_MIME, true), 404);
        abort_unless(Storage::disk($material->disk)->exists($material->path), 404);

        return response()->file(Storage::disk($material->disk)->path($material->path), [
            'Content-Type' => $material->mime,
            'Content-Disposition' => 'inline',
            'Accept-Ranges' => 'bytes',
            'X-Content-Type-Options' => 'nosniff',
        ]);
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

    /** Who may download a material: enrolled on its course (and in any
     * group/student audience), assigned trainer, or admin. */
    private function authorizeMaterial($user, Material $material): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $course = $material->course();
        abort_if($course === null, 403);

        if ($user->isTrainer()) {
            $assigned = TrainerAssignment::where('trainer_id', $user->id)
                ->where('course_id', $course->id)->exists();
            abort_unless($assigned, 403);

            return;
        }

        // Student — must be enrolled on the owning course AND inside the
        // material's audience (course-wide | group | individual).
        if ($material->attachable_type === Lesson::class) {
            abort_unless($material->attachable?->isPublished(), 403);
        }

        $enrolment = Enrolment::where('user_id', $user->id)
            ->where('status', 'active')
            ->whereHas('variant', fn ($q) => $q->where('course_id', $course->id))
            ->first();
        abort_unless($enrolment?->isActive(), 403);
        abort_unless($material->visibleTo($user), 403);
    }
}
