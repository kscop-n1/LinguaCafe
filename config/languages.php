<?php

/*
    Format:

    'language' => [
        'linguacafe_support' => bool, // can this language be learned in linguacafe 
        'tokenizer' => null|string, // name of the tokenizer if linguacafe_supported, otherwise null
        'install_required' => bool,
        'words_separated_by_spaces' => bool,
        'deepl_code' => null|string, // deepl code if supported, otherwise null
        'libre_translate_code' => null|string, // libre translate code if supported, otherwise null
        'my_memory_code' => null|string, // mymemory code if supported, otherwise null
        'website_import_support' => bool,
        'jellyfin_code' => null|string, // jellyfin code if linguacafe is supported, otherwise null.
        'jellyfin_filename_slug' => '', // filename slug jellyfin recognizes as a language if linguacafe is supported, otherwise null.
        'database_dictionary_table_name' => null|string, // database table slug if linguacafe_support is true, otherwise null. must be added if missing
        'dict_cc_code' => null|string, // dict cc code if supported, otherwise null
        'unicode_emoji' => string, // unicode emoji flag of the language. sometimes this does not match with the flag image in LC. '-' if there's no emoji
        'dictionaries' => array // list of dictionaries. only includes english dictionaries, it would be too much work to keep track of other ones
    ];


    jellyfin_code note:
    These are language codes that Jellyfin uses for subtitles. You can find out what
    a Jellyfin language code is by going to the Jellyfin subtitle import, and starting a video
    with a new language subtitle. If that new language is not added here, you will see
    a javascript log about unsupported languages code.

    missing information note:
    Some languages have partial information, so I just added them commented out to be used later.

*/


return [
    'afrikaans' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => null,
        'libre_translate_code' => null,
        'my_memory_code' => 'af',
        'website_import_support' => false,
        'jellyfin_code' => 'afr',
        'jellyfin_filename_slug' => 'af',
        'database_dictionary_table_name' => 'af',
        'dict_cc_code' => null,
        'unicode_emoji' => '🇿🇦',
        'dictionaries' => [
            'Wiktionary',
        ]
    ],
    'albanian' => [
        'linguacafe_support' => false,
        'tokenizer' => null,
        'install_required' => false,
        'words_separated_by_spaces' => true,
        'deepl_code' => null,
        'libre_translate_code' => 'sq',
        'my_memory_code' => null,
        'website_import_support' => false,
        'jellyfin_code' => null,
        'jellyfin_filename_slug' => null,
        'database_dictionary_table_name' => 'sq',
        'dict_cc_code' => 'SQ',
        'unicode_emoji' => '🇦🇱',
        'dictionaries' => [
            'Wiktionary',
        ]
    ],
    'arabic' => [
        'linguacafe_support' => false,
        'tokenizer' => null,
        'install_required' => false,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'AR',
        'libre_translate_code' => 'ar',
        'my_memory_code' => null,
        'website_import_support' => true,
        'jellyfin_code' => null,
        'jellyfin_filename_slug' => null,
        'database_dictionary_table_name' => null,
        'dict_cc_code' => null,
        'unicode_emoji' => '🇸🇦',
        'dictionaries' => [

        ]
    ],
    'armenian' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => null,
        'libre_translate_code' => null,
        'my_memory_code' => 'hy',
        'website_import_support' => false,
        'jellyfin_code' => 'arm',
        'jellyfin_filename_slug' => 'hy',
        'database_dictionary_table_name' => 'hye',
        'dict_cc_code' => null,
        'unicode_emoji' => '🇦🇲',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'basque' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => null,
        'libre_translate_code' => 'eu',
        'my_memory_code' => 'eu',
        'website_import_support' => false,
        'jellyfin_code' => 'baq',
        'jellyfin_filename_slug' => 'eu',
        'database_dictionary_table_name' => 'eus',
        'dict_cc_code' => null,
        'unicode_emoji' => '-',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'belarusian' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => null,
        'libre_translate_code' => null,
        'my_memory_code' => 'be',
        'website_import_support' => true,
        'jellyfin_code' => 'bel',
        'jellyfin_filename_slug' => 'be',
        'database_dictionary_table_name' => 'bel',
        'dict_cc_code' => null,
        'unicode_emoji' => '🇧🇾',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'bosnian' => [
        'linguacafe_support' => false,
        'tokenizer' => null,
        'install_required' => false,
        'words_separated_by_spaces' => true,
        'deepl_code' => null,
        'libre_translate_code' => null,
        'my_memory_code' => null,
        'website_import_support' => false,
        'jellyfin_code' => null,
        'jellyfin_filename_slug' => null,
        'database_dictionary_table_name' => 'bs',
        'dict_cc_code' => 'BS',
        'unicode_emoji' => '🇧🇦',
        'dictionaries' => [

        ]
    ],
    'buryat' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => null,
        'libre_translate_code' => null,
        'my_memory_code' => null,
        'website_import_support' => false,
        'jellyfin_code' => 'Buryat',
        'jellyfin_filename_slug' => 'Buryat',
        'database_dictionary_table_name' => 'bua',
        'dict_cc_code' => null,
        'unicode_emoji' => '-',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'bulgarian' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'BG',
        'libre_translate_code' => 'bg',
        'my_memory_code' => null,
        'website_import_support' => true,
        'jellyfin_code' => 'bul',
        'jellyfin_filename_slug' => 'bg',
        'database_dictionary_table_name' => 'bg',
        'dict_cc_code' => 'BG',
        'unicode_emoji' => '🇧🇬',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'brazilian portuguese' => [
        'linguacafe_support' => false,
        'tokenizer' => null,
        'install_required' => false,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'PT-BR',
        'libre_translate_code' => null,
        'my_memory_code' => null,
        'website_import_support' => false,
        'jellyfin_code' => null,
        'jellyfin_filename_slug' => null,
        'database_dictionary_table_name' => null,
        'dict_cc_code' => null,
        'unicode_emoji' => '🇧🇷',
        'dictionaries' => [
            
        ]
    ],
    'catalan' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => null,
        'libre_translate_code' => 'ca',
        'my_memory_code' => 'ca',
        'website_import_support' => false,
        'jellyfin_code' => 'cat',
        'jellyfin_filename_slug' => 'ca',
        'database_dictionary_table_name' => 'ca',
        'dict_cc_code' => null,
        'unicode_emoji' => '🇦🇩',
        'dictionaries' => [

        ]
    ],
    'chinese' => [
        'linguacafe_support' => true,
        'tokenizer' => 'spacy',
        'install_required' => true,
        'words_separated_by_spaces' => false,
        'deepl_code' => 'ZH',
        'libre_translate_code' => 'zh',
        'my_memory_code' => 'zh-CN',
        'website_import_support' => true,
        'jellyfin_code' => 'chi',
        'jellyfin_filename_slug' => 'zh',
        'database_dictionary_table_name' => 'zh',
        'dict_cc_code' => null,
        'unicode_emoji' => '🇨🇳',
        'dictionaries' => [
            'Wiktionary',
            'cc-cedict',
        ]
    ],
    // coptic tokenizer does not split words correctly
    // 'coptic' => [
    //     'linguacafe_support' => false,
    //     'tokenizer' => 'stanza',
    //     'install_required' => true,
    //     'words_separated_by_spaces' => true,
    //     'deepl_code' => null,
    //     'libre_translate_code' => null,
    //     'my_memory_code' => 'co',
    //     'website_import_support' => false,
    //     'jellyfin_code' => null,
    //     'jellyfin_filename_slug' => null,
    //     'database_dictionary_table_name' => 'cop',
    //     'dict_cc_code' => null,
    //     'unicode_emoji' => '-',
    //      'dictionaries' => [
    //          'Wiktionary'
    //      ],
    // ],
    'croatian' => [
        'linguacafe_support' => true,
        'tokenizer' => 'spacy',
        'install_required' => false,
        'words_separated_by_spaces' => true,
        'deepl_code' => null,
        'libre_translate_code' => null,
        'my_memory_code' => 'hr-HR',
        'website_import_support' => true,
        'jellyfin_code' => 'hrv',
        'jellyfin_filename_slug' => 'hr',
        'database_dictionary_table_name' => 'hr',
        'dict_cc_code' => 'HR',
        'unicode_emoji' => '🇭🇷',
        'dictionaries' => []
    ],
    'czech' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'CS',
        'libre_translate_code' => 'cs',
        'my_memory_code' => 'cs',
        'website_import_support' => false,
        'jellyfin_code' => 'cze',
        'jellyfin_filename_slug' => 'cs',
        'database_dictionary_table_name' => 'cs',
        'dict_cc_code' => 'CS',
        'unicode_emoji' => '🇨🇿',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'danish' => [
        'linguacafe_support' => true,
        'tokenizer' => 'spacy',
        'install_required' => false,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'DA',
        'libre_translate_code' => 'da',
        'my_memory_code' => 'da-DE',
        'website_import_support' => true,
        'jellyfin_code' => 'dan',
        'jellyfin_filename_slug' => 'da',
        'database_dictionary_table_name' => 'da',
        'dict_cc_code' => 'DA',
        'unicode_emoji' => '🇩🇰',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'dutch' => [
        'linguacafe_support' => true,
        'tokenizer' => 'spacy',
        'install_required' => false,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'NL',
        'libre_translate_code' => 'nl',
        'my_memory_code' => 'nl-NL',
        'website_import_support' => true,
        'jellyfin_code' => 'dut',
        'jellyfin_filename_slug' => 'nl',
        'database_dictionary_table_name' => 'nl',
        'dict_cc_code' => 'NL',
        'unicode_emoji' => '🇳🇱',
        'dictionaries' => [
            
        ]
    ],
    'english' => [
        'linguacafe_support' => true,
        'tokenizer' => 'spacy',
        'install_required' => false,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'EN-US',
        'libre_translate_code' => 'en',
        'my_memory_code' => 'en-GB',
        'website_import_support' => true,
        'jellyfin_code' => 'eng',
        'jellyfin_filename_slug' => 'en',
        'database_dictionary_table_name' => 'en',
        'dict_cc_code' => 'EN',
        'unicode_emoji' => '🇬🇧',
        'dictionaries' => [

        ]
    ],
    'erzya' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => null,
        'libre_translate_code' => null,
        'my_memory_code' => null,
        'website_import_support' => false,
        'jellyfin_code' => 'Erzya',
        'jellyfin_filename_slug' => 'Erzya',
        'database_dictionary_table_name' => 'myv',
        'dict_cc_code' => null,
        'unicode_emoji' => '-',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'esperanto' => [
        'linguacafe_support' => false,
        'tokenizer' => null,
        'install_required' => false,
        'words_separated_by_spaces' => true,
        'deepl_code' => null,
        'libre_translate_code' => 'eo',
        'my_memory_code' => null,
        'website_import_support' => false,
        'jellyfin_code' => null,
        'jellyfin_filename_slug' => null,
        'database_dictionary_table_name' => 'eo',
        'dict_cc_code' => 'EO',
        'unicode_emoji' => '-',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'estonian' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'ET',
        'libre_translate_code' => 'et',
        'my_memory_code' => 'et',
        'website_import_support' => true,
        'jellyfin_code' => 'est',
        'jellyfin_filename_slug' => 'et',
        'database_dictionary_table_name' => 'est',
        'dict_cc_code' => null,
        'unicode_emoji' => '🇪🇪',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'faroese' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => null,
        'libre_translate_code' => null,
        'my_memory_code' => 'fo',
        'website_import_support' => false,
        'jellyfin_code' => 'fao',
        'jellyfin_filename_slug' => 'fo',
        'database_dictionary_table_name' => 'fao',
        'dict_cc_code' => null,
        'unicode_emoji' => '🇫🇴',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'finnish' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'FI',
        'libre_translate_code' => 'fi',
        'my_memory_code' => 'fi-FI',
        'website_import_support' => true,
        'jellyfin_code' => 'fin',
        'jellyfin_filename_slug' => 'fi',
        'database_dictionary_table_name' => 'fi',
        'dict_cc_code' => 'FI',
        'unicode_emoji' => '🇫🇮',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'french' => [
        'linguacafe_support' => true,
        'tokenizer' => 'spacy',
        'install_required' => false,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'FR',
        'libre_translate_code' => 'fr',
        'my_memory_code' => 'fr-FR',
        'website_import_support' => true,
        'jellyfin_code' => 'fre',
        'jellyfin_filename_slug' => 'fr',
        'database_dictionary_table_name' => 'fr',
        'dict_cc_code' => 'FR',
        'unicode_emoji' => '🇫🇷',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'galician' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => null,
        'libre_translate_code' => 'gl',
        'my_memory_code' => 'gl',
        'website_import_support' => false,
        'jellyfin_code' => 'glg',
        'jellyfin_filename_slug' => 'gl',
        'database_dictionary_table_name' => 'glg',
        'dict_cc_code' => null,
        'unicode_emoji' => '-',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'german' => [
        'linguacafe_support' => true,
        'tokenizer' => 'spacy',
        'install_required' => false,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'DE',
        'libre_translate_code' => 'de',
        'my_memory_code' => 'de-DE',
        'website_import_support' => true,
        'jellyfin_code' => 'ger',
        'jellyfin_filename_slug' => 'de',
        'database_dictionary_table_name' => 'de',
        'dict_cc_code' => 'DE',
        'unicode_emoji' => '🇩🇪',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'greek' => [
        'linguacafe_support' => true,
        'tokenizer' => 'spacy',
        'install_required' => false,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'EL',
        'libre_translate_code' => 'el',
        'my_memory_code' => 'el-GR',
        'website_import_support' => true,
        'jellyfin_code' => 'gre',
        'jellyfin_filename_slug' => 'el',
        'database_dictionary_table_name' => 'el',
        'dict_cc_code' => 'EL',
        'unicode_emoji' => '🇬🇷',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    // need support for right to left text
    // 'hebrew' => [
    //     'linguacafe_support' => false,
    //     'tokenizer' => 'stanza',
    //     'install_required' => true,
    //     'words_separated_by_spaces' => true,
    //     'deepl_code' => null,
    //     'libre_translate_code' => 'he',
    //     'my_memory_code' => 'he',
    //     'website_import_support' => true,
    //     'jellyfin_code' => null,
    //     'jellyfin_filename_slug' => null,
    //     'database_dictionary_table_name' => 'heb',
    //     'dict_cc_code' => null,
    //     'unicode_emoji' => '🇮🇱',
    //     'dictionaries' => [
    //          'Wiktionary'
    //      ]
    // ],
    'hindi' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => null,
        'libre_translate_code' => 'hi',
        'my_memory_code' => 'hi',
        'website_import_support' => true,
        'jellyfin_code' => 'hin',
        'jellyfin_filename_slug' => 'hi',
        'database_dictionary_table_name' => 'hin',
        'dict_cc_code' => null,
        'unicode_emoji' => '🇮🇳',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'hungarian' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'HU',
        'libre_translate_code' => 'hu',
        'my_memory_code' => 'hu',
        'website_import_support' => true,
        'jellyfin_code' => 'hun',
        'jellyfin_filename_slug' => 'hu',
        'database_dictionary_table_name' => 'hun',
        'dict_cc_code' => 'HU',
        'unicode_emoji' => '🇭🇺',
        'dictionaries' => [
            'Wiktionary',
        ]
    ],
    'icelandic' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => null,
        'libre_translate_code' => null,
        'my_memory_code' => 'is',
        'website_import_support' => false,
        'jellyfin_code' => 'ice',
        'jellyfin_filename_slug' => 'is',
        'database_dictionary_table_name' => 'isl',
        'dict_cc_code' => 'IS',
        'unicode_emoji' => '🇮🇸',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'indonesian' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'ID',
        'libre_translate_code' => 'id',
        'my_memory_code' => 'id',
        'website_import_support' => true,
        'jellyfin_code' => 'ind',
        'jellyfin_filename_slug' => 'id',
        'database_dictionary_table_name' => 'ind',
        'dict_cc_code' => null,
        'unicode_emoji' => '🇮🇩',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'irish' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => null,
        'libre_translate_code' => false,
        'my_memory_code' => 'ga',
        'website_import_support' => false,
        'jellyfin_code' => 'gle',
        'jellyfin_filename_slug' => 'ga',
        'database_dictionary_table_name' => 'gle',
        'dict_cc_code' => null,
        'unicode_emoji' => '🇮🇪',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'italian' => [
        'linguacafe_support' => true,
        'tokenizer' => 'spacy',
        'install_required' => false,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'IT',
        'libre_translate_code' => 'it',
        'my_memory_code' => 'it-IT',
        'website_import_support' => true,
        'jellyfin_code' => 'ita',
        'jellyfin_filename_slug' => 'it',
        'database_dictionary_table_name' => 'it',
        'dict_cc_code' => 'IT',
        'unicode_emoji' => '🇮🇹',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'japanese' => [
        'linguacafe_support' => true,
        'tokenizer' => 'spacy',
        'install_required' => true,
        'words_separated_by_spaces' => false,
        'deepl_code' => 'JA',
        'libre_translate_code' => 'ja',
        'my_memory_code' => 'ja',
        'website_import_support' => true,
        'jellyfin_code' => 'jpn',
        'jellyfin_filename_slug' => 'ja',
        'database_dictionary_table_name' => 'jp',
        'dict_cc_code' => null,
        'unicode_emoji' => '🇯🇵',
        'dictionaries' => [
            'Wiktionary',
            'jmdict',
        ]
    ],
    'kazakh' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => null,
        'libre_translate_code' => null,
        'my_memory_code' => 'kk',
        'website_import_support' => false,
        'jellyfin_code' => 'kaz',
        'jellyfin_filename_slug' => 'kk',
        'database_dictionary_table_name' => 'kaz',
        'dict_cc_code' => null,
        'unicode_emoji' => '🇰🇿',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'korean' => [
        'linguacafe_support' => true,
        'tokenizer' => 'spacy',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'KO',
        'libre_translate_code' => 'ko',
        'my_memory_code' => 'ko-KR',
        'website_import_support' => true,
        'jellyfin_code' => 'kor',
        'jellyfin_filename_slug' => 'ko',
        'database_dictionary_table_name' => 'ko',
        'dict_cc_code' => null,
        'unicode_emoji' => '🇰🇷',
        'dictionaries' => [
            'Wiktionary',
            'kengdic',
        ]
    ],
    'kyrgyz' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => null,
        'libre_translate_code' => null,
        'my_memory_code' => 'ky',
        'website_import_support' => false,
        'jellyfin_code' => 'kir',
        'jellyfin_filename_slug' => 'ky',
        'database_dictionary_table_name' => 'kir',
        'dict_cc_code' => null,
        'unicode_emoji' => '🇰🇬',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    // 'kurmanji' => [
    // has stanza support, but found no dictionary at all, and it is a dialect
    // ],
    'latin' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => null,
        'libre_translate_code' => null,
        'my_memory_code' => 'la',
        'website_import_support' => false,
        'jellyfin_code' => 'lat',
        'jellyfin_filename_slug' => 'la',
        'database_dictionary_table_name' => 'la',
        'dict_cc_code' => 'LA',
        'unicode_emoji' => '-',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'latvian' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'LV',
        'libre_translate_code' => null,
        'my_memory_code' => 'lv',
        'website_import_support' => false,
        'jellyfin_code' => 'lav',
        'jellyfin_filename_slug' => 'lv',
        'database_dictionary_table_name' => 'lav',
        'dict_cc_code' => null,
        'unicode_emoji' => '🇱🇻',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    // Tokenizer splits NEWLINE words.
    // 'ligurian' => [
    //     'linguacafe_support' => true,
    //     'tokenizer' => 'stanza',
    //     'install_required' => true,
    //     'words_separated_by_spaces' => true,
    //     'deepl_code' => null,
    //     'libre_translate_code' => null,
    //     'my_memory_code' => 'lij',
    //     'website_import_support' => false,
    //     'jellyfin_code' => null,
    //     'jellyfin_filename_slug' => null,
    //     'database_dictionary_table_name' => 'lij',
    //     'dict_cc_code' => null,
    //     'unicode_emoji' => '-',
    //     'dictionaries' => [
    //         'Wiktionary'
    //     ],
    // ],
    'lithuanian' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'LT',
        'libre_translate_code' => null,
        'my_memory_code' => 'lt',
        'website_import_support' => true,
        'jellyfin_code' => 'lit',
        'jellyfin_filename_slug' => 'lt',
        'database_dictionary_table_name' => 'lit',
        'dict_cc_code' => null,
        'unicode_emoji' => '🇱🇹',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'macedonian' => [
        'linguacafe_support' => true,
        'tokenizer' => 'spacy',
        'install_required' => false,
        'words_separated_by_spaces' => true,
        'deepl_code' => null,
        'libre_translate_code' => null,
        'my_memory_code' => 'mk',
        'website_import_support' => true,
        'jellyfin_code' => 'mac',
        'jellyfin_filename_slug' => 'mk',
        'database_dictionary_table_name' => 'mk',
        'dict_cc_code' => null,
        'unicode_emoji' => '🇲🇰',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'maltese' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => null,
        'libre_translate_code' => null,
        'my_memory_code' => 'mt',
        'website_import_support' => false,
        'jellyfin_code' => 'mlt',
        'jellyfin_filename_slug' => 'mt',
        'database_dictionary_table_name' => 'mlt',
        'dict_cc_code' => null,
        'unicode_emoji' => '🇲🇹',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    // Tokenizer splits NEWLINE words.
    // 'manx' => [
    //     'linguacafe_support' => false,
    //     'tokenizer' => 'stanza',
    //     'install_required' => true,
    //     'words_separated_by_spaces' => true,
    //     'deepl_code' => null,
    //     'libre_translate_code' => null,
    //     'my_memory_code' => 'gv',
    //     'website_import_support' => false,
    //     'jellyfin_code' => null,
    //     'jellyfin_filename_slug' => null,
    //     'database_dictionary_table_name' => 'glv',
    //     'dict_cc_code' => null,
    //     'unicode_emoji' => '-',
    //     'dictionaries' => [
    //          'Wiktionary'
    //     ],
    // ],

    // Tokenizer splits words.
    // 'marathi' => [
    //     'linguacafe_support' => false,
    //     'tokenizer' => 'stanza',
    //     'install_required' => true,
    //     'words_separated_by_spaces' => true,
    //     'deepl_code' => null,
    //     'libre_translate_code' => null,
    //     'my_memory_code' => 'mr',
    //     'website_import_support' => false,
    //     'jellyfin_code' => 'mar',
    //     'jellyfin_filename_slug' => null,
    //     'database_dictionary_table_name' => null,
    //     'dict_cc_code' => null,
    //     'unicode_emoji' => '🇮🇳',
    //     'dictionaries' => [
    //          'Wiktionary'
    //     ]
    // ],

    // Uncommon language for learners, will add if someone requests to save some time
    // 'naija' => [
    //     'linguacafe_support' => true,
    //     'tokenizer' => 'stanza',
    // ],

    'north sami' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => null,
        'libre_translate_code' => null,
        'my_memory_code' => null,
        'website_import_support' => false,
        'jellyfin_code' => 'sme',
        'jellyfin_filename_slug' => 'se',
        'database_dictionary_table_name' => 'sme',
        'dict_cc_code' => null,
        'unicode_emoji' => '-',
        'dictionaries' => [
            'Wiktionary',
        ]
    ],

    'norwegian' => [
        'linguacafe_support' => true,
        'tokenizer' => 'spacy',
        'install_required' => false,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'NB',
        'libre_translate_code' => 'nb',
        'my_memory_code' => 'no',
        'website_import_support' => true,
        'jellyfin_code' => 'nor',
        'jellyfin_filename_slug' => 'no',
        'database_dictionary_table_name' => 'no',
        'dict_cc_code' => 'NO',
        'unicode_emoji' => '🇳🇴',
        'dictionaries' => [
            'Wiktionary',
        ]
    ],
    // right to left language
    // 'persian' => [
    //     'linguacafe_support' => true,
    //     'tokenizer' => 'stanza',
    //     'website_import_support' => true,
    // ],
    'polish' => [
        'linguacafe_support' => true,
        'tokenizer' => 'spacy',
        'install_required' => false,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'PL',
        'libre_translate_code' => 'pl',
        'my_memory_code' => 'pl',
        'website_import_support' => true,
        'jellyfin_code' => 'pol',
        'jellyfin_filename_slug' => 'pl',
        'database_dictionary_table_name' => 'pl',
        'dict_cc_code' => 'PL',
        'unicode_emoji' => '🇵🇱',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'portuguese' => [
        'linguacafe_support' => true,
        'tokenizer' => 'spacy',
        'install_required' => false,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'PT-PT',
        'libre_translate_code' => 'pt',
        'my_memory_code' => 'pt-PT',
        'website_import_support' => true,
        'jellyfin_code' => 'por',
        'jellyfin_filename_slug' => 'pt',
        'database_dictionary_table_name' => 'pt',
        'dict_cc_code' => 'PT',
        'unicode_emoji' => '🇵🇹',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'romanian' => [
        'linguacafe_support' => true,
        'tokenizer' => 'spacy',
        'install_required' => false,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'RO',
        'libre_translate_code' => 'ro',
        'my_memory_code' => 'ro',
        'website_import_support' => true,
        'jellyfin_code' => 'rum', // rum is not a misspell
        'jellyfin_filename_slug' => 'ro',
        'database_dictionary_table_name' => 'ro',
        'dict_cc_code' => 'RO',
        'unicode_emoji' => '🇷🇴',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'russian' => [
        'linguacafe_support' => true,
        'tokenizer' => 'spacy',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'RU',
        'libre_translate_code' => 'ru',
        'my_memory_code' => 'ru-RU',
        'website_import_support' => true,
        'jellyfin_code' => 'rus',
        'jellyfin_filename_slug' => 'ru',
        'database_dictionary_table_name' => 'ru',
        'dict_cc_code' => 'RU',
        'unicode_emoji' => '🇷🇺',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    // sentence segmentation does not work
    // 'sanskrit' => [
    //     'linguacafe_support' => false,
    //     'tokenizer' => 'stanza',
    //     'install_required' => true,
    //     'words_separated_by_spaces' => true,
    //     'deepl_code' => null,
    //     'libre_translate_code' => null,
    //     'my_memory_code' => 'sa',
    //     'website_import_support' => false,
    //     'jellyfin_code' => null,
    //     'jellyfin_filename_slug' => null,
    //     'database_dictionary_table_name' => 'san',
    //     'dict_cc_code' => null,
    //     'unicode_emoji' => '🇮🇳',
    //     'dictionaries' => [
    //         'Wiktionary',
    //     ]
    // ],
    'serbian' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => null,
        'libre_translate_code' => null,
        'my_memory_code' => 'sr',
        'website_import_support' => true,
        'jellyfin_code' => 'srp',
        'jellyfin_filename_slug' => 'sr',
        'database_dictionary_table_name' => 'srp',
        'dict_cc_code' => 'SR',
        'unicode_emoji' => '🇷🇸',
        'dictionaries' => [

        ]
    ],
    'slovak' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'SK',
        'libre_translate_code' => 'sk',
        'my_memory_code' => 'sk',
        'website_import_support' => false,
        'jellyfin_code' => 'slo',
        'jellyfin_filename_slug' => 'sk',
        'database_dictionary_table_name' => 'slk',
        'dict_cc_code' => 'SK',
        'unicode_emoji' => '🇸🇰',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'slovenian' => [
        'linguacafe_support' => true,
        'tokenizer' => 'spacy',
        'install_required' => false,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'SL',
        'libre_translate_code' => 'sl',
        'my_memory_code' => 'sl',
        'website_import_support' => true,
        'jellyfin_code' => 'slv',
        'jellyfin_filename_slug' => 'sl',
        'database_dictionary_table_name' => 'sl',
        'dict_cc_code' => null,
        'unicode_emoji' => '🇸🇮',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'spanish' => [
        'linguacafe_support' => true,
        'tokenizer' => 'spacy',
        'install_required' => false,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'ES',
        'libre_translate_code' => 'es',
        'my_memory_code' => 'es-ES',
        'website_import_support' => true,
        'jellyfin_code' => 'spa',
        'jellyfin_filename_slug' => 'es',
        'database_dictionary_table_name' => 'es',
        'dict_cc_code' => 'ES',
        'unicode_emoji' => '🇪🇸',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    // 'swahili' => [
    //     'website_import_support' => true,
    //     'dictionaries' => [
    //         'Wiktionary'
    //     ]
    // ],
    'swedish' => [
        'linguacafe_support' => true,
        'tokenizer' => 'spacy',
        'install_required' => false,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'SV',
        'libre_translate_code' => 'sv',
        'my_memory_code' => 'sv-SE',
        'website_import_support' => true,
        'jellyfin_code' => 'swe',
        'jellyfin_filename_slug' => 'sv',
        'database_dictionary_table_name' => 'sv',
        'dict_cc_code' => 'SV',
        'unicode_emoji' => '🇸🇪',
        'dictionaries' => [

        ]
    ],
    'tamil' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => null,
        'libre_translate_code' => null,
        'my_memory_code' => 'ta',
        'website_import_support' => false,
        'jellyfin_code' => 'tam',
        'jellyfin_filename_slug' => 'ta',
        'database_dictionary_table_name' => 'tam',
        'dict_cc_code' => null,
        'unicode_emoji' => '🇮🇳',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'telugu' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => null,
        'libre_translate_code' => null,
        'my_memory_code' => 'te',
        'website_import_support' => false,
        'jellyfin_code' => 'tel',
        'jellyfin_filename_slug' => 'te',
        'database_dictionary_table_name' => 'tel',
        'dict_cc_code' => null,
        'unicode_emoji' => '🇮🇳',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'thai' => [
        'linguacafe_support' => true,
        'tokenizer' => 'spacy',
        'install_required' => true,
        'words_separated_by_spaces' => false,
        'deepl_code' => null,
        'libre_translate_code' => 'th',
        'my_memory_code' => 'th',
        'website_import_support' => false,
        'jellyfin_code' => 'tha',
        'jellyfin_filename_slug' => 'th',
        'database_dictionary_table_name' => 'th',
        'dict_cc_code' => null,
        'unicode_emoji' => '🇹🇭',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'turkish' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'TR',
        'libre_translate_code' => 'tr',
        'my_memory_code' => 'tr-TR',
        'website_import_support' => true,
        'jellyfin_code' => 'tur',
        'jellyfin_filename_slug' => 'tr',
        'database_dictionary_table_name' => 'tr',
        'dict_cc_code' => 'TR',
        'unicode_emoji' => '🇹🇷',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    'ukrainian' => [
        'linguacafe_support' => true,
        'tokenizer' => 'spacy',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => 'UK',
        'libre_translate_code' => 'uk',
        'my_memory_code' => 'uk',
        'website_import_support' => true,
        'jellyfin_code' => 'ukr',
        'jellyfin_filename_slug' => 'uk',
        'database_dictionary_table_name' => 'ua',
        'dict_cc_code' => null,
        'unicode_emoji' => '🇺🇦',
        'dictionaries' => [
            'Wiktionary'
        ]
    ],
    
    // right to left
    // 'urdu' => [
    // 'dictionaries' => [
    //     'Wiktionary'
    // ]
    // ],
    
    // right to left
    // 'uyghur' => [
    // 'dictionaries' => [
    //     'Wiktionary'
    // ]
    // ],
    
    // 'vietnamese' => [
    //     'linguacafe_support' => false,
    //     'tokenizer' => 'stanza',
    //     'install_required' => true,
    //     'words_separated_by_spaces' => true,
    //     'deepl_code' => null,
    //     'libre_translate_code' => null,
    //     'my_memory_code' => 'vi',
    //     'website_import_support' => true,
    //     'jellyfin_code' => null,
    //     'jellyfin_filename_slug' => null,
    //     'database_dictionary_table_name' => 'vie',
    //     'dict_cc_code' => null,
    //     'unicode_emoji' => '🇻🇳',
    //     'dictionaries' => [
    //         'Wiktionary'
    //     ]
    // ],

    'welsh' => [
        'linguacafe_support' => true,
        'tokenizer' => 'stanza',
        'install_required' => true,
        'words_separated_by_spaces' => true,
        'deepl_code' => null,
        'libre_translate_code' => null,
        'my_memory_code' => 'cy-GB',
        'website_import_support' => false,
        'jellyfin_code' => 'wel',
        'jellyfin_filename_slug' => 'cy',
        'database_dictionary_table_name' => 'cy',
        'dict_cc_code' => null,
        'unicode_emoji' => '🏴󠁧󠁢󠁷󠁬󠁳󠁿',
        'dictionaries' => [
            'Wiktionary',
            'eurfa',
        ]
    ],
];