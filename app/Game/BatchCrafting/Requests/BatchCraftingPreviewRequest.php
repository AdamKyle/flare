<?php

namespace App\Game\BatchCrafting\Requests;

use App\Game\BatchCrafting\Values\BatchCraftingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BatchCraftingPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $startableTypes = [
            BatchCraftingType::CRAFT->value,
            BatchCraftingType::CRAFT_AND_ENCHANT->value,
            BatchCraftingType::ENCHANT->value,
            BatchCraftingType::ALCHEMY->value,
            BatchCraftingType::HOLY_OILS->value,
            BatchCraftingType::TRINKETRY->value,
        ];

        return [
            'batch_type' => ['required', Rule::in($startableTypes)],
            'selected_items' => ['nullable', 'array'],
            'selected_items.*' => ['integer'],
            'selected_oils' => ['nullable', 'array'],
            'selected_oils.*' => ['integer'],
            'progress' => ['nullable', 'array'],
            'progress.craft_mode' => ['nullable', 'string', Rule::in(['specific_item', 'experience', 'event', 'craft_set', 'craft_enchant_set'])],
            'progress.specific_crafting_type' => ['nullable', 'string'],
            'progress.specific_item_id' => ['nullable', 'integer', 'min:1'],
            'progress.craft_amount' => ['nullable', 'integer', 'min:1'],
            'progress.enchant_affix_ids' => ['nullable', 'array', 'max:2'],
            'progress.enchant_affix_ids.*' => ['integer', 'min:1'],
            'progress.selected_set_id' => ['nullable', 'integer', 'min:1'],
            'progress.craft_enchant_set_mode' => ['nullable', 'string', Rule::in(['build_new'])],
            'progress.enchant_plan' => ['nullable', 'array'],
            'progress.craft_set_plan' => ['nullable', 'array'],
            'progress.alchemy_mode' => ['nullable', 'string', Rule::in(['experience', 'amount'])],
            'progress.alchemy_item_id' => ['nullable', 'integer', 'min:1'],
            'progress.alchemy_amount' => ['nullable', 'integer', 'min:1'],
            'progress.holy_oil_mode' => ['nullable', 'string', Rule::in(['selected', 'set'])],
        ];
    }
}
