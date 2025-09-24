<?php

namespace App\Http\Requests\Dictionaries\Search;

use Illuminate\Foundation\Http\FormRequest;

class SearchInflectionsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'term' => [
                'required',
                'string',
            ],
        ];
    }
}
