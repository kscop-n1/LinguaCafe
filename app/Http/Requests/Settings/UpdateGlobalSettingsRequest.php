<?php

namespace App\Http\Requests\Settings;

use App\Rules\ValidCronExpression;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGlobalSettingsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'settings' => [
                'required',
                'array',
            ],
            'settings.backupInterval' => [
                'string',
                new ValidCronExpression,
            ],
        ];
    }
}
