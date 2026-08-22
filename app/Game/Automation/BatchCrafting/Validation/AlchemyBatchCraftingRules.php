<?php

namespace App\Game\Automation\BatchCrafting\Validation;

use App\Game\Automation\BatchCrafting\Enums\AlchemyBatchMode;
use App\Game\Automation\BatchCrafting\Requests\BatchCraftingRequest;
use App\Game\Automation\BatchCrafting\Validation\Concerns\ListingPriceRules;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class AlchemyBatchCraftingRules
{
    use ListingPriceRules;

    /**
     * Build the Alchemy batch type's validation rules for the requested mode.
     *
     * @param  array  $requestData  The raw Batch Crafting request data.
     * @return array The Alchemy validation rules.
     */
    public function rules(array $requestData): array
    {
        $mode = $requestData['progress']['alchemy_mode'] ?? null;

        return [
            'progress' => ['required', Rule::array($this->allowedProgressKeys($mode))],
            'progress.alchemy_mode' => ['required', new Enum(AlchemyBatchMode::class)],
            ...$this->modeSpecificRules($mode),
        ];
    }

    /**
     * Return the client-owned progress keys allowed for the requested mode.
     *
     * @param  string|null  $mode  The requested mode value.
     * @return array<int, string> The allowed client-owned progress keys.
     */
    private function allowedProgressKeys(?string $mode): array
    {
        return match ($mode) {
            AlchemyBatchMode::AMOUNT->value => ['alchemy_mode', 'alchemy_item_id', 'alchemy_amount', 'listing_price'],
            AlchemyBatchMode::EXPERIENCE->value => ['alchemy_mode', 'listing_price'],
            default => ['alchemy_mode'],
        };
    }

    /**
     * Return the mode-specific validation rules for the requested mode.
     *
     * @param  string|null  $mode  The requested mode value.
     * @return array The mode-specific validation rules.
     */
    private function modeSpecificRules(?string $mode): array
    {
        return match ($mode) {
            AlchemyBatchMode::AMOUNT->value => [
                'progress.alchemy_item_id' => ['required', 'integer', 'min:1'],
                'progress.alchemy_amount' => ['required', 'integer', 'min:1', 'max:'.BatchCraftingRequest::MAX_CRAFT_AMOUNT],
                ...$this->listingPriceRules(),
            ],
            AlchemyBatchMode::EXPERIENCE->value => $this->listingPriceRules(),
            default => [],
        };
    }
}
