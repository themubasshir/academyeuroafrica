<?php

namespace App\Services\Video;

use App\Models\CourseVideo;
use App\Services\Video\Contracts\VideoProvider;
use App\Services\Video\Providers\GenericProvider;

class VideoManager
{
    /** Resolve the provider for a stored video record. */
    public function provider(CourseVideo $video): VideoProvider
    {
        return match ($video->provider) {
            // 'vimeo' => new Providers\VimeoProvider,   // wire once account exists
            // 'cloudflare' => new Providers\CloudflareProvider,
            default => new GenericProvider,
        };
    }

    /**
     * Player/embed URL for an authorised viewer. Caller must already have
     * authorised enrolment — this only resolves the URL.
     */
    public function embedUrl(CourseVideo $video): string
    {
        return $this->provider($video)->embedUrl($video);
    }
}
