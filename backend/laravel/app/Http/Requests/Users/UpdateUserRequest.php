<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
                'min:2',
                'max:64',
            ],
            'email' => [
                'required',
                'email',
            ],
            'isAdmin' => [
                'required',
                'boolean',
            ],
        ];
    }
}
