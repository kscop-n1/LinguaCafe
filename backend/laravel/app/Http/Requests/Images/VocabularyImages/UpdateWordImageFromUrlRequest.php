<?php

namespace App\Http\Requests\Images\VocabularyImages;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWordImageFromUrlRequest extends FormRequest
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
