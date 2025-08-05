<?php

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;

class GetReviewItemsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'practiceMode' => [
                'required',
                'boolean',
            ],
        ];
    }
}
