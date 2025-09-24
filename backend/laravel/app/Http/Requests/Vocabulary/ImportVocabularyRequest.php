<?php

namespace App\Http\Requests\Vocabulary;

use Illuminate\Foundation\Http\FormRequest;

class ImportVocabularyRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'importFile' => [
                'required',
                'file',
            ],
            'onlyUpdate' => [
                'required',
                'boolean',
            ],
            'skipHeader' => [
                'required',
                'boolean',
            ],
            'delimiter' => [
                'required',
                'string',
                'min:1',
                'max:1',
            ],
        ];
    }

    // TODO: Check if I could rewrite these to use url params for these booleans instead
    protected function prepareForValidation(): void
    {
        /*
            This is required because on the javascript side a FormData object is used,
            and for some reason it messes up the received data type in laravel.
        */
        $this->merge([
            'onlyUpdate' => $this->onlyUpdate === 'true',
            'skipHeader' => $this->skipHeader === 'true',
        ]);
    }
}
