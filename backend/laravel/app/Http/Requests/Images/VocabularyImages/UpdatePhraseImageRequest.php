<?php

namespace App\Http\Requests\Images\VocabularyImages;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePhraseImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'imageFile' => [
                'required',
                'file',
            ],
        ];
    }
}
