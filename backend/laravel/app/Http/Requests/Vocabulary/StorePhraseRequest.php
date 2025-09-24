<?php

namespace App\Http\Requests\Vocabulary;

use Illuminate\Foundation\Http\FormRequest;

class StorePhraseRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'words' => [
                'required',
                'json',
            ],
            'stage' => [
                'required',
                'integer',
                'gte:-7',
                'lte:2',
            ],
            'reading' => [
                'nullable',
                'string',
            ],
            'translation' => [
                'nullable',
                'string',
            ],
        ];
    }
}
