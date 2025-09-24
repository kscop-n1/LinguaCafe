<?php

namespace App\Http\Requests\Goals;

use App\Enums\GoalTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCalendarDataRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'goalType' => [
                'required',
                Rule::enum(GoalTypeEnum::class),
            ],
            'day' => [
                'required',
                'string',
            ],
            'quantity' => [
                'required',
                'integer',
            ],
        ];
    }
}
