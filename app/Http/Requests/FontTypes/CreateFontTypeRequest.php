<?php

namespace App\Http\Requests\FontTypes;

use Illuminate\Foundation\Http\FormRequest;

class CreateFontTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|min:2|max:128',
            'languages' => 'required|array',
            'languages.*' => 'string',
            'fontFile' => 'required|file',
        ];
    }
}
