<?php

namespace App\Game\Kingdoms\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KingdomEmbezzelRequest extends FormRequest
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
            'embezzel_amount' => 'required|int',
        ];
    }

    public function messages() {
        return [
            'embezzel_amount.required' => 'Amount to embezzel is required.',
        ];
    }
}
