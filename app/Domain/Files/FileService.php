<?php

namespace App\Domain\Files;

use App\Models\AssignmentAttachment;
use App\Models\Material;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Secure upload + authorised download for learning materials and attachments.
 * Stored on the private disk under randomised names; never web-reachable.
 */
class FileService
{
    public const ALLOWED_MIME = [
        'application/pdf', 'application/zip', 'application/x-zip-compressed',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'audio/mpeg', 'audio/mp3',
        'image/jpeg', 'image/png', 'image/webp',
        'text/plain',
    ];

    public const MAX_BYTES = 25 * 1024 * 1024; // 25 MB

    public const VIDEO_MIME = ['video/mp4', 'video/webm'];

    public const MAX_VIDEO_BYTES = 512 * 1024 * 1024; // 512 MB; host PHP limits may be lower

    /** Store an upload as a Material attached to a model. */
    public function storeMaterial(UploadedFile $file, User $uploader, $attachable, string $visibility = 'enrolled'): Material
    {
        $mime = $file->getMimeType();
        abort_unless(in_array($mime, self::ALLOWED_MIME, true), 422, 'File type not allowed.');
        abort_if($file->getSize() > self::MAX_BYTES, 422, 'File too large.');

        $ext = $file->guessExtension() ?: 'bin';
        $path = 'materials/'.Str::random(40).'.'.$ext;
        Storage::disk('local')->put($path, $file->getContent());

        return Material::create([
            'uploader_id' => $uploader->id,
            'attachable_type' => $attachable->getMorphClass(),
            'attachable_id' => $attachable->getKey(),
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $mime,
            'size_bytes' => $file->getSize(),
            'visibility' => $visibility,
        ]);
    }

    /** Store a PDF or uploaded video used as the primary body of a lesson. */
    public function storeLessonContent(UploadedFile $file, User $uploader, $lesson, string $type): Material
    {
        $mime = (string) $file->getMimeType();
        $allowed = $type === 'pdf' ? ['application/pdf'] : self::VIDEO_MIME;
        $maximum = $type === 'pdf' ? self::MAX_BYTES : self::MAX_VIDEO_BYTES;

        abort_unless(in_array($mime, $allowed, true), 422, 'The uploaded file does not match the lesson type.');
        abort_if($file->getSize() > $maximum, 422, 'File too large.');

        $directory = $type === 'pdf' ? 'materials' : 'lesson-videos';
        $extension = $file->guessExtension() ?: ($type === 'pdf' ? 'pdf' : 'mp4');
        $path = $directory.'/'.Str::random(40).'.'.$extension;
        Storage::disk('local')->put($path, $file->getContent());

        return Material::create([
            'uploader_id' => $uploader->id,
            'attachable_type' => $lesson->getMorphClass(),
            'attachable_id' => $lesson->getKey(),
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $mime,
            'size_bytes' => $file->getSize(),
            'visibility' => 'enrolled',
        ]);
    }

    /** Replace a lesson body only after the new, type-appropriate file is safely stored. */
    public function replaceLessonContent(Material $material, UploadedFile $file, string $type): Material
    {
        $mime = (string) $file->getMimeType();
        $allowed = $type === 'pdf' ? ['application/pdf'] : self::VIDEO_MIME;
        $maximum = $type === 'pdf' ? self::MAX_BYTES : self::MAX_VIDEO_BYTES;

        abort_unless(in_array($mime, $allowed, true), 422, 'The uploaded file does not match the lesson type.');
        abort_if($file->getSize() > $maximum, 422, 'File too large.');

        $directory = $type === 'pdf' ? 'materials' : 'lesson-videos';
        $extension = $file->guessExtension() ?: ($type === 'pdf' ? 'pdf' : 'mp4');
        $path = $directory.'/'.Str::random(40).'.'.$extension;
        $oldPath = $material->path;

        Storage::disk($material->disk)->put($path, $file->getContent());
        $material->forceFill([
            'path' => $path,
            'mime' => $mime,
            'size_bytes' => $file->getSize(),
            'original_name' => $file->getClientOriginalName(),
        ])->save();
        Storage::disk($material->disk)->delete($oldPath);

        return $material->fresh();
    }

    /** Replace a stored material after applying the same MIME and size policy. */
    public function replaceMaterial(Material $material, UploadedFile $file): Material
    {
        $mime = (string) $file->getMimeType();
        $isVideo = in_array($mime, self::VIDEO_MIME, true);
        abort_unless($isVideo || in_array($mime, self::ALLOWED_MIME, true), 422, 'File type not allowed.');
        abort_if($file->getSize() > ($isVideo ? self::MAX_VIDEO_BYTES : self::MAX_BYTES), 422, 'File too large.');

        $directory = $isVideo ? 'lesson-videos' : 'materials';
        $path = $directory.'/'.Str::random(40).'.'.($file->guessExtension() ?: 'bin');
        Storage::disk($material->disk)->put($path, $file->getContent());
        Storage::disk($material->disk)->delete($material->path);
        $material->forceFill([
            'path' => $path,
            'mime' => $mime,
            'size_bytes' => $file->getSize(),
            'original_name' => $file->getClientOriginalName(),
        ])->save();

        return $material->fresh();
    }

    /** Stream an authorised download (caller must authorise first). */
    public function download(Material $material)
    {
        abort_unless(Storage::disk($material->disk)->exists($material->path), 404);

        return Storage::disk($material->disk)->download(
            $material->path,
            $material->original_name,
            ['Content-Type' => $material->mime]
        );
    }

    /** Store a student submission attachment (stricter — no executables ever). */
    public function storeSubmissionFile(UploadedFile $file, $submission): AssignmentAttachment
    {
        $mime = $file->getMimeType();
        abort_unless(in_array($mime, self::ALLOWED_MIME, true), 422, 'File type not allowed.');
        abort_if($file->getSize() > self::MAX_BYTES, 422, 'File too large.');

        $ext = $file->guessExtension() ?: 'bin';
        $path = 'submissions/'.Str::random(40).'.'.$ext;
        Storage::disk('local')->put($path, $file->getContent());

        return AssignmentAttachment::create([
            'assignment_submission_id' => $submission->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $mime,
            'size_bytes' => $file->getSize(),
        ]);
    }
}
