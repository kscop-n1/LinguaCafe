<?php

namespace App\Http\Requests\Vocabulary;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWordRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'stage' => 'integer|gte:-7|lte:2',
            'translation' => 'nullable|string',
            'reading' => 'nullable|string',
            'lemma' => 'nullable|string',
            'lemma_reading' => 'nullable|string',
            'lookup_count' => 'nullable|integer|gte:0',
            'read_count' => 'nullable|integer|gte:0',
            'relearning' => 'nullable|boolean',
        ];
    }
}
