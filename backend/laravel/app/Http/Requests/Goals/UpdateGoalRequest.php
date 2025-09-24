<?php

namespace App\Http\Requests\Goals;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGoalRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'newGoalQuantity' => [
                'required',
                'numeric',
                'gte:0',
            ],
        ];
    }
}
