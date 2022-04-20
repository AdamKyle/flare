<?php

namespace App\Game\Kingdoms\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KingdomUnitRecruitmentRequest extends FormRequest
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
            'amount'           => 'required|integer',
            'recruitment_type' => 'required|in:recruit-normally,recruit-with-gold',
            'total_cost'       => 'required|integer',
        ];
    }

    public function messages() {
        return [
            'amount.required'           => 'Amount is missing',
            'recruitment_type.required' => 'Recruitment type is missing',
            'total_cost.required'       => 'Total cost is missing',
        ];
    }
}
