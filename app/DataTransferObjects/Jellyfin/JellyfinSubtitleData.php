<?php

namespace App\DataTransferObjects\Jellyfin;

class JellyfinSubtitleData
{
    public function __construct(
        public string $language,
        public bool $supportedLanguage,
        public array $text,
    ) {
        //
    }
}
