<?php

namespace App\Game\Kingdoms\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KingdomEmbezzleRequest extends FormRequest
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
            'embezzle_amount' => 'required|int',
        ];
    }

    public function messages() {
        return [
            'embezzle_amount.required' => 'Amount to embezzle is required.',
        ];
    }
}
