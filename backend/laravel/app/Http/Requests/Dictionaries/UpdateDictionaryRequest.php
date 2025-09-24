<?php

namespace App\Http\Requests\Dictionaries;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDictionaryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => [
                'string',
            ],
            'api_host' => [
                'nullable',
                'string',
            ],
            'source_language' => [
                'string',
            ],
            'target_language' => [
                'string',
            ],
            'color' => [
                'string',
            ],
            'enabled' => [
                'boolean',
            ],
        ];
    }
}
