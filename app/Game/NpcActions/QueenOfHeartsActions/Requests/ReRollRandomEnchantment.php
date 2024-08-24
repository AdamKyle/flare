<?php

namespace App\Game\NpcActions\QueenOfHeartsActions\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReRollRandomEnchantment extends FormRequest
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
            'selected_affix' => 'required|string|in:prefix,suffix,all-enchantments',
            'selected_reroll_type' => 'required|string|in:base,stats,skills,damage,resistance,everything',
        ];
    }

    public function messages()
    {
        return [
            'selected_slot_id.required' => 'Invalid input.',
            'selected_affix.required' => 'Invalid input.',
            'selected_reroll_type.required' => 'Invalid input.',
        ];
    }
}
