<?php

namespace App\Http\Requests\Images\VocabularyImages;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePhraseImageFromUrlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => [
                'required',
                'string',
                'url',
            ],
        ];
    }
}
