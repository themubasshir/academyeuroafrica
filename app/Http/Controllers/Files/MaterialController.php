<?php

namespace App\Http\Controllers\Files;

use App\Domain\Files\FileService;
use App\Http\Controllers\Controller;
use App\Models\AssignmentAttachment;
use App\Models\Course;
use App\Models\Enrolment;
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
        $enrolled = Enrolment::where('user_id', $user->id)
            ->where('status', 'active')
            ->whereHas('variant', fn ($q) => $q->where('course_id', $course->id))
            ->exists();
        abort_unless($enrolled, 403);
        abort_unless($material->visibleTo($user), 403);
    }
}
