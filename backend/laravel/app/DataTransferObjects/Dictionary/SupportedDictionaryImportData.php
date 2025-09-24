<?php

namespace App\DataTransferObjects\Dictionary;

class SupportedDictionaryImportData
{
    public function __construct(
        public string $name,
        public string $databaseName,
        // TODO: should be LanguageConfig
        public string $sourceLanguage,
        public string $targetLanguage,
        public string $color,
        public int $expectedRecordCount,
        public string $fileName
    ) {
        //
    }
}
