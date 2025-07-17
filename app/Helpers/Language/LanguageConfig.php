<?php

namespace App\Helpers\Language;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class LanguageConfig
{
    public function __construct(
        public string $name,
        public ?string $databaseDictionaryTableName,
        public bool $linguacafeSupport,
        public bool $installRequired,
        public bool $wordsSeparatedBySpaces,
        public bool $websiteImportSupport,
        public ?string $tokenizer,
        public ?string $deeplCode,
        public ?string $libreTranslateCode,
        public ?string $myMemoryCode,
        public ?string $jellyfinCode,
        public ?string $dictCcCode,
        public ?string $emoji,
    )
    {
        //
    }

    /* Helper methods */
    public function requiresInstall(): bool
    {
        return $this->installRequired;
    }
    
    public function hasSpaces(): bool
    {
        return $this->wordsSeparatedBySpaces;
    }

    public function hasLinguaCafeSupport(): bool
    {
        return $this->linguacafeSupport;
    }

    public function hasDeeplSupport(): bool
    {
        return $this->deeplCode !== null;
    }

    public function hasLibreTranslateSupport(): bool
    {
        return $this->libreTranslateCode !== null;
    }

    public function hasMyMemorySupport(): bool
    {
        return $this->myMemoryCode !== null;
    }

    public function hasDictCcSupport(): bool
    {
        return $this->dictCcCode !== null;
    }

    public function hasWebsiteImportSupport(): bool
    {
        return $this->websiteImportSupport;
    }

    /* Retrieve static functions */
    public static function all(): Collection
    {
        $languageObjects = collect();

        $languages = collect(config('languages'));

        $languages->each(function(array $languageData, string $languageName) use($languageObjects) {
            $languageObjects->push(self::create($languageName, $languageData));
        });

        return $languageObjects;
    }

    public static function load(string $language): ?self
    {
        $languages = config('languages');
        $language = Str::lower($language);

        if (!isset($languages[$language])) {
            return null;
        }
        
        return self::create($language, $languages[$language]);
    }

    private static function create(string $language, array $configData): self
    {
        return new self(
            name: $language,
            databaseDictionaryTableName: $configData['database_dictionary_table_name'],
            linguacafeSupport: $configData['linguacafe_support'],
            installRequired: $configData['install_required'],
            wordsSeparatedBySpaces: $configData['words_separated_by_spaces'],
            websiteImportSupport: $configData['website_import_support'],
            tokenizer: $configData['tokenizer'],
            deeplCode: $configData['deepl_code'],
            libreTranslateCode: $configData['libre_translate_code'],
            myMemoryCode: $configData['my_memory_code'],
            jellyfinCode: $configData['jellyfin_code'],
            dictCcCode: $configData['dict_cc_code'],
            emoji: $configData['unicode_emoji'],
        );
    }
}