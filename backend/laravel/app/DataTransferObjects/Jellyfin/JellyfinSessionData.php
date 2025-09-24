<?php

namespace App\DataTransferObjects\Jellyfin;

class JellyfinSessionData
{
    public function __construct(
        public string $client,
        public string $userName,
        public string $userId,
        public string $title,
        public string $type,
        public ?string $seriesName,
        public ?string $seriesEpisode,
        public ?string $seriesSeason,
        public ?string $movieName,
        public string $runTimeTicks,
        public string $nowPlayingItemId,
        public string $sessionId,
        public string $mediaSourceId,
        public array $subtitles,
    ) {
        //
    }
}
