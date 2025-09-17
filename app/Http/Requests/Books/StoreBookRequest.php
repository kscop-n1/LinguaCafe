<?php

namespace App\Http\Requests\Books;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'max:128',
            ],
            'cover' => [
                'file',
                'mimes:jpg,jpeg,png,webp',
            ],
        ];
    }
}
