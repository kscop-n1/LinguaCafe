<?php

namespace App\Http\Requests\Goals;

use App\Enums\GoalTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCalendarDataRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
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
