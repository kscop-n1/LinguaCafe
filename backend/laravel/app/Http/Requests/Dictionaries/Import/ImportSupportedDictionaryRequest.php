<?php

namespace App\Http\Requests\Dictionaries\Import;

use Illuminate\Foundation\Http\FormRequest;

class ImportSupportedDictionaryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'dictionaryName' => [
                'required',
                'string',
            ],
            'dictionaryFileName' => [
                'required',
                'string',
            ],
            'dictionarySourceLanguage' => [
                'required',
                'string',
            ],
            'dictionaryTargetLanguage' => [
                'required',
                'string',
            ],
            'dictionaryDatabaseName' => [
                'required',
                'string',
            ],
        ];
    }
}
