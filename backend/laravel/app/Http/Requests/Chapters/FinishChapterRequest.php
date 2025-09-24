<?php

namespace App\Http\Requests\Chapters;

use Illuminate\Foundation\Http\FormRequest;

class FinishChapterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'uniqueWords' => [
                'required',
                'json',
            ],
            'autoLevelUpWords' => [
                'required',
                'boolean',
            ],
            'leveledUpWords' => [
                'required',
                'json',
            ],
            'leveledUpPhrases' => [
                'required',
                'json',
            ],
            'autoMoveWordsToKnown' => [
                'required',
                'boolean',
            ],
        ];
    }
}
