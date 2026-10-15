<?php

namespace App\Services\Video\Contracts;

use App\Models\CourseVideo;
use App\Models\User;

/**
 * External streaming provider abstraction (Vimeo / Cloudflare Stream / Bunny
 * Stream / Mux…). Implementations must not leak credentials and must only
 * issue playback URLs/tokens for authorised requests.
 *
 * Provider choice is deferred pending account+cost verification — see
 * `Services\Video\Providers`. A `GenericProvider` embeds whatever is already
 * stored (no new uploads) so development is unblocked.
 */
interface VideoProvider
{
    /** Machine name, e.g. 'vimeo'. */
    public function name(): string;

    /** Embed/player URL for an authorised playback of this video. */
    public function embedUrl(CourseVideo $video): string;

    /**
     * Optional signed/temporary playback URL where the provider supports it.
     * Return null if unsupported (plain embed is used).
     */
    public function signedPlaybackUrl(CourseVideo $video, User $viewer): ?string;

    /** Whether this provider is configured and usable in production. */
    public function isConfigured(): bool;
}
