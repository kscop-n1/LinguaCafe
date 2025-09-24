<?php

namespace App\Http\Requests\FontTypes;

use Illuminate\Foundation\Http\FormRequest;

class StoreFontTypeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:128',
            ],
            'languages' => [
                'required',
                'array',
            ],
            'languages.*' => [
                'string',
            ],
            'fontFile' => [
                'required',
                'file',
            ],
        ];
    }
}
