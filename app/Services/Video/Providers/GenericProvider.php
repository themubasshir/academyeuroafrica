<?php

namespace App\Services\Video\Providers;

use App\Models\CourseVideo;
use App\Models\User;
use App\Services\Video\Contracts\VideoProvider;

/**
 * Fallback provider: renders a player iframe for externally-hosted videos that
 * already expose an embed URL pattern (YouTube privacy-enhanced, Vimeo public
 * embed, direct MP4). Used until a real streaming account is wired in.
 */
class GenericProvider implements VideoProvider
{
    public function name(): string
    {
        return 'generic';
    }

    public function embedUrl(CourseVideo $video): string
    {
        $id = $video->provider_video_id;

        return match (true) {
            // YouTube nocookie embed
            preg_match('/^[\w-]{11}$/', $id) === 1 => 'https://www.youtube-nocookie.com/embed/'.$id,
            // Vimeo numeric id
            ctype_digit($id) => 'https://player.vimeo.com/video/'.$id,
            // Already a URL (mp4/embed)
            str_starts_with($id, 'http') => $id,
            default => 'about:blank',
        };
    }

    public function signedPlaybackUrl(CourseVideo $video, User $viewer): ?string
    {
        return null;
    }

    public function isConfigured(): bool
    {
        return true; // no credentials needed
    }
}
