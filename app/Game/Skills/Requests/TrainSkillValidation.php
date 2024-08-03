<?php

namespace App\Game\Skills\Requests;

use App\Game\Core\Rules\SkillXPPercentage;
use Illuminate\Foundation\Http\FormRequest;

class TrainSkillValidation extends FormRequest
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
            'skill_id' => 'integer|required',
            'xp_percentage' => ['required', new SkillXPPercentage],
        ];
    }

    public function messages()
    {
        return [
            'skill_id.required' => 'Error. Invalid Input.',
            'xp_percentage.required' => 'Error. Invalid Input.',
            'xp_percentage.decimal' => 'Error. Invalid Input.',
        ];
    }
}
