<?php

namespace App\Game\Shop\Requests;

use App\Game\Core\Items\Values\EquippablePositionType;
use App\Game\Core\Items\Values\ItemCatalogType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ShopReplaceItemValidation extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for buying a Shop Item to replace an equipped Item.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'position' => ['required', 'string', Rule::enum(EquippablePositionType::class)],
            'slot_id' => 'required',
            'equip_type' => [
                'required',
                'string',
                Rule::enum(ItemCatalogType::class)->except([ItemCatalogType::CENSOR, ItemCatalogType::TRINKET, ItemCatalogType::QUEST, ItemCatalogType::ALCHEMY]),
            ],
            'item_id_to_buy' => 'required|integer|exists:items,id',
        ];
    }

    /**
     * Get the validation messages for the replacement selection.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'position.required' => 'You must select a position for your item',
            'position.'.Enum::class => 'Error. Invalid Input.',
            'slot_id.required' => 'Error. Invalid Input.',
            'equip_type.required' => 'Error. Invalid Input.',
            'equip_type.'.Enum::class => 'Error. Invalid Input.',
        ];
    }
}
