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
