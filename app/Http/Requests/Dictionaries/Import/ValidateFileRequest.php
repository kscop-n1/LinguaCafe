<?php

namespace App\Http\Requests\Dictionaries\Import;

use Illuminate\Foundation\Http\FormRequest;

class ValidateFileRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'dictionaryFile' => [
                'required',
                'file',
            ],
        ];
    }
}
