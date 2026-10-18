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
        $id = trim($video->provider_video_id);

        if (preg_match('/^[\w-]{11}$/', $id) === 1) {
            return 'https://www.youtube-nocookie.com/embed/'.$id;
        }

        if (ctype_digit($id)) {
            return 'https://player.vimeo.com/video/'.$id;
        }

        $host = strtolower((string) parse_url($id, PHP_URL_HOST));
        $path = trim((string) parse_url($id, PHP_URL_PATH), '/');

        if (in_array($host, ['youtu.be', 'www.youtu.be'], true)
            && preg_match('/^[\w-]{11}$/', $path) === 1) {
            return 'https://www.youtube-nocookie.com/embed/'.$path;
        }

        if (in_array($host, ['youtube.com', 'www.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], true)) {
            parse_str((string) parse_url($id, PHP_URL_QUERY), $query);
            $youtubeId = $query['v'] ?? str($path)->after('embed/')->before('/')->toString();
            if (preg_match('/^[\w-]{11}$/', $youtubeId) === 1) {
                return 'https://www.youtube-nocookie.com/embed/'.$youtubeId;
            }
        }

        if (in_array($host, ['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'], true)) {
            $vimeoId = collect(explode('/', $path))->last(fn ($part) => ctype_digit($part));
            if ($vimeoId !== null) {
                return 'https://player.vimeo.com/video/'.$vimeoId;
            }
        }

        return 'about:blank';
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
