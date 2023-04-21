<?php

namespace App\Game\Mercenaries\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseMercenaryRequest extends FormRequest
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
            'type' => 'required|string',
        ];
    }

    public function messages() {
        return [
            'type' => 'missing type'
        ];
    }
}
