<?php

namespace App\Enums;

enum DictionaryTypeEnum: string
{
    case SUPPORTED = 'supported';
    case CUSTOM = 'custom';
    case DEEPL = 'deepl';
    case MY_MEMORY = 'my_memory';
    case LIBRE_TRANSLATE = 'libre_translate';
    case CUSTOM_API = 'custom_api';

    public static function apiTypes(): array
    {
        return [
            self::DEEPL,
            self::MY_MEMORY,
            self::LIBRE_TRANSLATE,
            self::CUSTOM_API,
        ];
    }
}
