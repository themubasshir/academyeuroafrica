<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Number;

#[Fillable([
    'uploader_id', 'attachable_type', 'attachable_id', 'disk', 'path',
    'original_name', 'description', 'mime', 'size_bytes', 'visibility',
    'group_id', 'student_id', 'source_key',
])]
class Material extends Model
{
    use HasFactory;

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    /** @return BelongsTo<Group, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /** @return BelongsTo<User, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /** @return MorphTo<Model, $this> */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /** The course this material belongs to, regardless of attach level. */
    public function course(): ?Course
    {
        return match ($this->attachable_type) {
            Course::class => $this->attachable,
            CourseModule::class => $this->attachable?->course,
            Lesson::class => $this->attachable?->module?->course,
            default => null,
        };
    }

    /** Audience check: course-wide → group → individual. Enrolment is checked by the caller. */
    public function visibleTo(User $user): bool
    {
        if ($user->isAdmin() || $this->uploader_id === $user->id) {
            return true;
        }

        // Trainer manuals / answer keys — students can never see these.
        if ($this->visibility === 'trainers') {
            return $user->isTrainer();
        }

        if ($this->student_id !== null) {
            return $this->student_id === $user->id;
        }

        if ($this->group_id !== null) {
            return GroupMember::where('group_id', $this->group_id)
                ->where('user_id', $user->id)->exists()
                || TrainerAssignment::where('trainer_id', $user->id)
                    ->where('course_id', $this->course()?->id)->exists();
        }

        return true; // course-wide: enrolment check happens in the controller/policy
    }

    /** Human-readable size, e.g. "48 KB". */
    public function humanSize(): string
    {
        return Number::fileSize($this->size_bytes, precision: 0);
    }

    /** Short type label from the mime, e.g. "PDF", "ZIP". */
    public function typeLabel(): string
    {
        return match (true) {
            str_contains($this->mime, 'pdf') => 'PDF',
            str_contains($this->mime, 'zip') => 'ZIP',
            str_contains($this->mime, 'wordprocessing') => 'DOCX',
            str_contains($this->mime, 'presentation') => 'PPTX',
            str_contains($this->mime, 'spreadsheet') => 'XLSX',
            str_contains($this->mime, 'mpeg'), str_contains($this->mime, 'mp3') => 'MP3',
            str_contains($this->mime, 'jpeg') => 'JPEG',
            str_contains($this->mime, 'png') => 'PNG',
            str_contains($this->mime, 'webp') => 'WEBP',
            str_contains($this->mime, 'text') => 'TXT',
            default => strtoupper(pathinfo($this->original_name, PATHINFO_EXTENSION) ?: 'FILE'),
        };
    }
}
