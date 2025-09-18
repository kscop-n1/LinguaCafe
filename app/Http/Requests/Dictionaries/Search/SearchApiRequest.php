<?php

namespace App\Http\Requests\Dictionaries\Search;

use Illuminate\Foundation\Http\FormRequest;

class SearchApiRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'language' => [
                'required',
                'string',
            ],
            'term' => [
                'required',
                'string',
            ],
            'context' => [
                'string',
            ],
        ];
    }
}
