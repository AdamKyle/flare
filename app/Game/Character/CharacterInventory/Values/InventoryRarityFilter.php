<?php

namespace App\Game\Character\CharacterInventory\Values;

use App\Flare\Models\Item;

enum InventoryRarityFilter: string
{
    case UNIQUE = 'unique';
    case MYTHIC = 'mythic';
    case COSMIC = 'cosmic';
    case SINGLE_ENCHANTED = 'single-enchanted';
    case DUAL_ENCHANTED = 'dual-enchanted';

    /**
     * Determine whether the Item matches this rarity filter.
     *
     * @param Item $item
     * @return bool
     */
    public function matches(Item $item): bool
    {
        $isNormalRarity = ! $item->is_unique && ! $item->is_mythic && ! $item->is_cosmic;

        return match ($this) {
            self::COSMIC => $item->is_cosmic === true,
            self::MYTHIC => $item->is_mythic === true && $item->is_cosmic === false,
            self::UNIQUE => $item->is_unique === true && $item->is_mythic === false && $item->is_cosmic === false,
            self::SINGLE_ENCHANTED => $item->affix_count === 1 && $isNormalRarity && $item->holy_stacks_applied === 0,
            self::DUAL_ENCHANTED => $item->affix_count === 2 && $isNormalRarity && $item->holy_stacks_applied === 0,
        };
    }
}
