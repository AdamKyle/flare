<?php

namespace App\Game\Market\Requests;

use App\Game\Core\Items\Values\EquippablePositionType;
use App\Game\Core\Items\Values\ItemCatalogType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class MarketBuyAndReplaceRequest extends FormRequest
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
     * Get the validation rules for choosing the equipped Item a Market purchase replaces.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'position' => ['required', 'string', Rule::enum(EquippablePositionType::class)],
            'slot_id' => 'required|integer',
            'equip_type' => [
                'required',
                'string',
                Rule::enum(ItemCatalogType::class)->except([ItemCatalogType::CENSOR, ItemCatalogType::QUEST, ItemCatalogType::ALCHEMY]),
            ],
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
            'position.required' => 'Select the position the purchased item should be equipped in.',
            'position.'.Enum::class => 'Select a valid equipment position.',
            'slot_id.required' => 'Select the equipped item to replace.',
            'equip_type.required' => 'The item type to equip is missing.',
            'equip_type.'.Enum::class => 'The item type to equip is not valid.',
        ];
    }
}
