<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Game\Character\CharacterInventory\Values\InventoryRarityFilter;
use Illuminate\Database\Eloquent\Collection;

class CharacterInventoryFilterService
{
    /**
     * Apply Backpack item-type and rarity filters to inventory slots.
     *
     * @param Collection $slots
     * @param array $filters
     * @return Collection
     */
    public function filter(Collection $slots, array $filters): Collection
    {
        $itemTypes = $filters['item_types'] ?? [];

        if (is_array($itemTypes) && count($itemTypes) > 0) {
            $slots = $slots->filter(
                fn ($slot): bool => in_array($slot->item->type, $itemTypes, true)
            );
        }

        $rarity = InventoryRarityFilter::tryFrom($filters['rarity'] ?? '');

        if (! is_null($rarity)) {
            $slots = $slots->filter(
                fn ($slot): bool => $rarity->matches($slot->item)
            );
        }

        return $slots->values();
    }
}
