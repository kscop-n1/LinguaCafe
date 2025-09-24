<?php

namespace App\Http\Requests\Dictionaries\Import;

use Illuminate\Foundation\Http\FormRequest;

class ImportDictionaryCsvFileRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'dictionary' => [
                'required',
                'file',
            ],
            'delimiter' => [
                'required',
                'string',
                'max:1',
            ],
            'skipHeader' => [
                'required',
                'string',
                'in:true,false',
            ],
            'dictionaryName' => [
                'required',
                'string',
            ],
            'databaseName' => [
                'required',
                'string',
            ],
            'sourceLanguage' => [
                'required',
                'string',
            ],
            'targetLanguage' => [
                'required',
                'string',
            ],
            'color' => [
                'required',
                'string',
            ],
        ];
    }
}
