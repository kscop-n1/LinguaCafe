<?php

namespace App\Http\Requests\Vocabulary;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchKanjiRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'kanjiGroupBy' => [
                'required',
                'string',
                Rule::in(['grade', 'jlpt']),
            ],
            'showUnknown' => [
                'required',
                'boolean',
            ],
        ];
    }
}
