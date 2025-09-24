<?php

namespace App\Http\Requests\Vocabulary;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrUpdateExampleSentenceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'targetId' => [
                'required',
                'numeric',
                'gte:0',
            ],
            'targetType' => [
                'required',
                'string',
                Rule::in(['word', 'phrase']),
            ],
            // TODO: should be plain array instead of json
            'exampleSentenceWords' => [
                'required',
                'json',
            ],
        ];
    }
}
