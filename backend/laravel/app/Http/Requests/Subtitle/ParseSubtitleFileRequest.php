<?php

namespace App\Http\Requests\Subtitle;

use Illuminate\Foundation\Http\FormRequest;

class ParseSubtitleFileRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'subtitleFile' => [
                'required',
                'file',
            ],
        ];
    }
}
