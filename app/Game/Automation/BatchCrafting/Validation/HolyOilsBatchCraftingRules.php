<?php

namespace App\Game\Automation\BatchCrafting\Validation;

use App\Game\Automation\BatchCrafting\Enums\HolyOilsBatchMode;
use App\Game\Automation\BatchCrafting\Validation\Concerns\ListingPriceRules;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class HolyOilsBatchCraftingRules
{
    use ListingPriceRules;

    /**
     * Build the Holy Oils batch type's validation rules for the requested mode.
     *
     * The client sends only selected target/oil slot ids; costs, stack counts, and target
     * eligibility remain entirely backend-authoritative.
     *
     * @param array $requestData The raw Batch Crafting request data.
     * @return array The Holy Oils validation rules.
     */
    public function rules(array $requestData): array
    {
        $mode = $requestData['progress']['holy_oils_mode'] ?? null;

        return [
            'progress' => ['required', Rule::array($this->allowedProgressKeys($mode))],
            'progress.holy_oils_mode' => ['required', new Enum(HolyOilsBatchMode::class)],
            'progress.oil_slot_ids' => ['required', 'array', 'min:1'],
            'progress.oil_slot_ids.*' => ['integer', 'min:1'],
            ...$this->modeSpecificRules($mode),
            ...$this->listingPriceRules(),
        ];
    }

    /**
     * Return the client-owned progress keys allowed for the requested mode.
     *
     * @param string|null $mode The requested mode value.
     * @return array<int, string> The allowed client-owned progress keys.
     */
    private function allowedProgressKeys(?string $mode): array
    {
        return match ($mode) {
            HolyOilsBatchMode::SELECTED_ITEMS->value => ['holy_oils_mode', 'target_slot_ids', 'oil_slot_ids', 'listing_price'],
            HolyOilsBatchMode::INVENTORY_SET->value => ['holy_oils_mode', 'inventory_set_id', 'oil_slot_ids', 'listing_price'],
            default => ['holy_oils_mode'],
        };
    }

    /**
     * Return the mode-specific validation rules for the requested mode.
     *
     * @param string|null $mode The requested mode value.
     * @return array The mode-specific validation rules.
     */
    private function modeSpecificRules(?string $mode): array
    {
        return match ($mode) {
            HolyOilsBatchMode::SELECTED_ITEMS->value => [
                'progress.target_slot_ids' => ['required', 'array', 'min:1'],
                'progress.target_slot_ids.*' => ['integer', 'min:1'],
            ],
            HolyOilsBatchMode::INVENTORY_SET->value => [
                'progress.inventory_set_id' => ['required', 'integer', 'min:1'],
            ],
            default => [],
        };
    }
}
