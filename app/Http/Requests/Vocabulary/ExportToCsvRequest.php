<?php

namespace App\Http\Requests\Vocabulary;

use Illuminate\Foundation\Http\FormRequest;

class ExportToCsvRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'text' => [
                'required',
                'string',
            ],
            'book' => [
                'required',
                'numeric',
            ],
            'chapter' => [
                'required',
                'numeric',
            ],
            'stage' => [
                'required',
                'numeric',
            ],
            'phrases' => [
                'required',
                'string',
            ],
            'orderBy' => [
                'required',
                'string',
            ],
            'translation' => [
                'required',
                'string',
            ],
            'fields' => [
                'required',
                'array',
            ],
        ];
    }
}
