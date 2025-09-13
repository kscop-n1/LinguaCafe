<?php

namespace App\Http\Requests\Vocabulary;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePhraseRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'stage' => 'integer|gte:-7|lte:0',
            'translation' => 'nullable|string',
            'reading' => 'nullable|string',
            'lookup_count' => 'nullable|integer|gte:0',
            'relearning' => 'nullable|boolean',
        ];
    }
}
