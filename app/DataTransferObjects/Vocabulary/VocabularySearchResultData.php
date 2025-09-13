<?php

namespace App\DataTransferObjects\Vocabulary;

use Illuminate\Support\Collection;

class VocabularySearchResultData
{
    public function __construct(
        public int $wordCount,
        public Collection $words,
        public Collection $books,
        public int $pageCount,
        public int $currentPage,
        public bool $languageSpaces
    ) {
        //
    }
}
