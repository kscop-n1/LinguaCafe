<?php

namespace App\Enums\Import;

/*
    These are import types coming from frontend.
*/
enum ImportTypeEnum: string
{
    case E_BOOK = 'e-book';
    case JELLYFIN_SUBTITLE = 'jellyfin-subtitle';
    case SUBTITLE_FILE = 'subtitle-file';
    case PLAIN_TEXT = 'plain-text';
    case TEXT_FILE = 'text-file';
    case YOUTUBE = 'youtube';
    case WEBSITE = 'website';
}
