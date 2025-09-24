<?php

namespace App\Http\Requests\Chapters;

use Illuminate\Foundation\Http\FormRequest;

class GetChapterForEditorRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'chapterId' => [
                'required',
                'numeric',
                'gte:0',
            ],
        ];
    }
}
