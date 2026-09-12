<?php

namespace App\Game\Automation\BatchCrafting\Validation;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\EnchantingBatchMode;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class EnchantBatchCraftingRules
{
    /**
     * Build the Enchant batch type's validation rules.
     *
     * Enchant For Event is the only Enchant workflow: no item selection, no affix selection,
     * no manual Event inventory selector, no listing, and no output destination.
     *
     * @param array $requestData The raw Batch Crafting request data.
     * @return array The Enchant validation rules.
     */
    public function rules(array $requestData): array
    {
        return [
            'disposition' => ['required', new Enum(BatchCraftingDisposition::class), Rule::in([BatchCraftingDisposition::KEEP->value])],
            'progress' => ['required', Rule::array(['enchant_mode'])],
            'progress.enchant_mode' => ['required', new Enum(EnchantingBatchMode::class), Rule::in([EnchantingBatchMode::EVENT->value])],
        ];
    }
}
