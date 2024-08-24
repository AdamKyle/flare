<?php

namespace App\Game\NpcActions\QueenOfHeartsActions\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveRandomEnchantment extends FormRequest
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
            'selected_slot_id' => 'required|integer|exists:inventory_slots,id',
            'selected_secondary_slot_id' => 'required|integer|exists:inventory_slots,id',
            'selected_affix' => 'required|string|in:prefix,suffix,all-enchantments',
        ];
    }

    public function messages()
    {
        return [
            'selected_slot_id.required' => 'Missing selected slot id.',
            'selected_secondary_slot_id.required' => 'Missing secondary inventory slot id.',
            'selected_affix.required' => 'Missing selected Affix.',
        ];
    }
}
