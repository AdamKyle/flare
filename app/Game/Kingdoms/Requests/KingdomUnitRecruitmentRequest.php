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
            'amount'           => 'required|numeric',
            'recruitment_type' => 'required|in:gold,resources',
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
