<?php

namespace App\DataTransferObjects\Dictionary;

class CsvDictionaryImportSampleData
{
    public function __construct(
        public string $status,
        public array $sample,
        public int $recordCount,
    ) {
        //
    }
}