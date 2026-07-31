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
            self::TRINKETRY => 'trinketry_currencies',
            default => 'gold',
        };
    }

    public function usesPendingCountdown(array $progress): bool
    {
        return match ($this) {
            self::HOLY_OILS => true,
            self::ALCHEMY => ($progress['alchemy_mode'] ?? 'experience') === 'amount',
            self::CRAFT, self::CRAFT_AND_ENCHANT => in_array($progress['craft_mode'] ?? 'experience', ['specific_item', 'craft_set', 'craft_enchant_set'], true),
            self::ENCHANT => ($progress['enchant_mode'] ?? 'event') === 'set',
            self::TRINKETRY => false,
        };
    }

    public function usesEightHourTimer(array $progress): bool
    {
        return ! $this->usesPendingCountdown($progress);
    }

    public function isExperienceMode(array $progress): bool
    {
        return match ($this) {
            self::CRAFT, self::CRAFT_AND_ENCHANT => ($progress['craft_mode'] ?? 'experience') === 'experience',
            self::ALCHEMY => ($progress['alchemy_mode'] ?? 'experience') === 'experience',
            self::TRINKETRY => true,
            self::ENCHANT, self::HOLY_OILS => false,
        };
    }
}
