<?php

namespace App\Http\Requests\Dictionaries\Import;

use Illuminate\Foundation\Http\FormRequest;

class ValidateDictionaryCsvFileRequest extends FormRequest
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
        ];
    }
}
