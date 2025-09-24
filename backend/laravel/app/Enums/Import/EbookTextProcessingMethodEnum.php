<?php

namespace App\Enums\Import;

enum EbookTextProcessingMethodEnum: string
{
    case PLAINTEXT = 'plaintext';
    case PRESERVE_BLOCK_TAGS = 'preserve_block_tags';
}
