<?php

namespace App\Game\Kingdoms\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DropItemsOnKingdomRequest extends FormRequest
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
            'slots'   => 'required|array',
            'slots.*' => 'required|integer|exists:inventory_slots,id',
        ];
    }

    public function messages() {
        return [
            'slots.required'  => 'You are missing items to use.',
            'slots.*.integer' => 'Each item must be an integer.',
        ];
    }
}
