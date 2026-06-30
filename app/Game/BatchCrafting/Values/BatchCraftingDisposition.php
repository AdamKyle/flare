<?php

namespace App\Game\BatchCrafting\Values;

enum BatchCraftingDisposition: string
{
    case KEEP = 'keep';
    case KEEP_HIGHEST = 'keep_highest';
    case SELL = 'sell';
    case DESTROY = 'destroy';
    case LIST = 'list';
    case DISENCHANT = 'disenchant';
    case KEEP_BEST_SELL_REST = 'keep_best_sell_rest';
    case KEEP_BEST_DISENCHANT_REST = 'keep_best_disenchant_rest';

    public function label(): string
    {
        return match ($this) {
            self::KEEP => 'Keep',
            self::KEEP_HIGHEST => 'Keep Highest Level Crafted At The End',
            self::SELL => 'Sell',
            self::DESTROY => 'Destroy',
            self::LIST => 'List',
            self::DISENCHANT => 'Disenchant',
            self::KEEP_BEST_SELL_REST => 'Keep Best and Sell Rest',
            self::KEEP_BEST_DISENCHANT_REST => 'Keep Best and Disenchant Rest',
        };
    }

    public function isAllowedFor(BatchCraftingType $type): bool
    {
        if ($this === self::DISENCHANT) {
            return $type === BatchCraftingType::CRAFT_AND_ENCHANT;
        }

        if (in_array($this, [self::KEEP_BEST_SELL_REST, self::KEEP_BEST_DISENCHANT_REST], true)) {
            return $type === BatchCraftingType::CRAFT_AND_ENCHANT;
        }

        if ($type === BatchCraftingType::HOLY_OILS) {
            return $this === self::KEEP;
        }

        if ($this !== self::LIST) {
            return true;
        }

        return in_array($type, [
            BatchCraftingType::CRAFT_AND_ENCHANT,
            BatchCraftingType::ALCHEMY,
        ], true);
    }
}
