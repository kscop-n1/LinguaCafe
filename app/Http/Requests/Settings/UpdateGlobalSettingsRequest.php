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
            'settings' => ['required', 'array'],
            'settings.backupCompression' => ['boolean'],
            'settings.backupInterval' => ['string', new ValidCronExpression],
            'settings.backupRetainDaily' => ['integer', 'between:0,50'],
            'settings.backupRetainWeekly' => ['integer', 'between:0,50'],
            'settings.backupRetainMonthly' => ['integer', 'between:0,50'],
            'settings.backupRetainYearly' => ['integer', 'between:0,50'],
            'settings.backupRetainMostRecent' => ['integer', 'between:0,50'],
        ];
    }
}
