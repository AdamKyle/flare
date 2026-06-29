<?php

namespace App\Game\BatchCrafting\Values;

enum BatchCraftingType: string
{
    case CRAFT = 'craft';
    case CRAFT_AND_ENCHANT = 'craft_and_enchant';
    case ENCHANT = 'enchant';
    case ALCHEMY = 'alchemy';
    case HOLY_OILS = 'holy_oils';
    case TRINKETRY = 'trinketry';

    public function label(): string
    {
        return match ($this) {
            self::CRAFT => 'Craft',
            self::CRAFT_AND_ENCHANT => 'Craft and Enchant',
            self::ENCHANT => 'Enchant',
            self::ALCHEMY => 'Alchemy',
            self::HOLY_OILS => 'Holy Oils',
            self::TRINKETRY => 'Trinketry',
        };
    }

    public function requiredCurrency(): string
    {
        return match ($this) {
            self::ALCHEMY, self::HOLY_OILS => 'gold_dust',
            self::TRINKETRY => 'shards',
            default => 'gold',
        };
    }
}
