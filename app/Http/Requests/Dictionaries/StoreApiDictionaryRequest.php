<?php

namespace App\Http\Requests\Dictionaries;

use App\Enums\DictionaryTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAPIDictionaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sourceLanguage' => [
                'required',
                'string',
            ],
            'targetLanguage' => [
                'required',
                'string',
            ],
            'color' => [
                'required',
                'string',
            ],
            'name' => [
                'required',
                'string',
            ],
            'type' => [
                'required',
                Rule::in(DictionaryTypeEnum::apiTypes()),
            ],
            'api_host' => [
                'required_if:type,custom_api',
                'string',
            ],
        ];
    }
}
