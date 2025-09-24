<?php

namespace App\DataTransferObjects\Vocabulary;

use Illuminate\Support\Collection;

class KanjiSearchResultData
{
    public function __construct(
        public Collection $kanji,
        public Collection $total,
        public Collection $known,
    ) {
        //
    }
}
