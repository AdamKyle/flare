<?php

namespace App\Game\Kingdoms\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NPCKingdomPurchaseRequest extends FormRequest
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
            'kingdom_id' => 'required|exists:kingdoms,id',
        ];
    }

    public function messages()
    {
        return [
            'kingdom_id.required' => 'Kingdom ID is required',
            'kingdom_id.exists' => 'Kingdom does not exist.',
        ];
    }
}
