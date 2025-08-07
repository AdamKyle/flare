<?php

namespace App\Game\Skills\Requests;

use App\Flare\Items\Values\ItemType;
use Illuminate\Foundation\Http\FormRequest;

class CraftingValidation extends FormRequest
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
            'item_to_craft' => 'required|integer',
            'type' => 'required|in:' . implode(',', ItemType::allWeaponTypes()) . ',armour,spell,' . ItemType::RING->value,
            'craft_for_npc' => 'required|bool',
            'craft_for_event' => 'required|bool',
        ];
    }

    public function messages()
    {
        return [
            'item_to_craft.required' => 'What item are you trying to craft?',
            'type.required' => 'Missing type.',
            'type.in' => 'Invalid input.',
            'craft_for_npc.required' => 'Are we crafting for an npc?',
            'craft_for_event.required' => 'Are we crafting for an event?',
        ];
    }
}
