<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
                'min:4',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
            ],
            'isAdmin' => [
                'required',
                'boolean',
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                'min:8',
                'max:32',
            ],
            // TODO: password_confirmation -> passwordConfirmation
            'password_confirmation' => [
                'required',
                'string',
            ],
        ];
    }
}
