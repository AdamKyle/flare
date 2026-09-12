<?php

namespace App\Game\Automation\BatchCrafting\Enums;

enum BatchCraftingType: string
{
    case CRAFT = 'craft';
    case CRAFT_AND_ENCHANT = 'craft_and_enchant';
    case ENCHANT = 'enchant';
    case ALCHEMY = 'alchemy';
    case HOLY_OILS = 'holy_oils';
    case TRINKETRY = 'trinketry';

    /**
     * Return the persisted progress key holding this batch type's mode value.
     *
     * @return string The progress mode key for this batch type.
     */
    public function progressModeKey(): string
    {
        return match ($this) {
            self::CRAFT => 'craft_mode',
            self::CRAFT_AND_ENCHANT => 'craft_enchant_mode',
            self::ENCHANT => 'enchant_mode',
            self::ALCHEMY => 'alchemy_mode',
            self::HOLY_OILS => 'holy_oils_mode',
            self::TRINKETRY => 'trinketry_mode',
        };
    }

    /**
     * Return this batch type's persisted mode value from the given progress data.
     *
     * @param array $progress The persisted Batch Crafting progress data.
     * @return string The persisted mode value.
     */
    public function modeFromProgress(array $progress): string
    {
        return $progress[$this->progressModeKey()];
    }
}
