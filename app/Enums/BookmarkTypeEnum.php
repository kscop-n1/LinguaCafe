<?php

namespace App\Enums;

enum BookmarkTypeEnum: string
{
    case NEXT_CHAPTER = 'next_chapter';
    case CUSTOM = 'custom';
}