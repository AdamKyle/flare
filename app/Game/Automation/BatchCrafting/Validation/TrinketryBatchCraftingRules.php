<?php

namespace App\Game\Automation\BatchCrafting\Validation;

use App\Game\Automation\BatchCrafting\Enums\TrinketryBatchMode;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class TrinketryBatchCraftingRules
{
    /**
     * Build the Trinketry batch type's validation rules.
     *
     * No item selector, no amount, no output destination, and no listing price.
     *
     * @param  array  $requestData  The raw Batch Crafting request data.
     * @return array The Trinketry validation rules.
     */
    public function rules(array $requestData): array
    {
        return [
            'progress' => ['required', Rule::array(['trinketry_mode'])],
            'progress.trinketry_mode' => ['required', new Enum(TrinketryBatchMode::class)],
        ];
    }
}
