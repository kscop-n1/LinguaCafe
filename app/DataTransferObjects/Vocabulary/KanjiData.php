<?php

namespace App\DataTransferObjects\Vocabulary;

use App\Models\Kanji;
use Illuminate\Support\Collection;

class KanjiData
{
    public function __construct(
        public Kanji $kanji,
        public Collection $words,
        public ?Collection $radicals,
    ) {
        //
    }
}
