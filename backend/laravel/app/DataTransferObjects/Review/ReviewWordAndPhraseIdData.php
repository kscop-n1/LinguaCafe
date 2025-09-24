<?php

namespace App\DataTransferObjects\Review;

use Illuminate\Support\Collection;

class ReviewWordAndPhraseIdData
{
    public function __construct(
        public Collection $wordIds,
        public Collection $phraseIds,
    ) {
        //
    }
}
