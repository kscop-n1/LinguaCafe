<?php

namespace App\DataTransferObjects\Vocabulary;

class CsvImportResultData
{
    public function __construct(
        public int $createdWords,
        public int $updatedWords,
        public int $rejectedWords,
    ) {
        //
    }
}
