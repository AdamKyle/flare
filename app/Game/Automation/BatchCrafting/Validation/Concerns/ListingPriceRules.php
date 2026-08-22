<?php

namespace App\Game\Automation\BatchCrafting\Validation\Concerns;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;

trait ListingPriceRules
{
    /**
     * Return the shared listing price validation rules for a workflow that permits the List disposition.
     *
     * @return array The listing price validation rules.
     */
    private function listingPriceRules(): array
    {
        return [
            'progress.listing_price' => [
                'required_if:disposition,'.BatchCraftingDisposition::LIST->value,
                'prohibited_unless:disposition,'.BatchCraftingDisposition::LIST->value,
                'integer', 'min:1',
            ],
        ];
    }
}
