<?php

namespace App\Game\Core\Items\Values;

enum ItemUsabilityType: int
{
    case STAT_INCREASE = 0;
    case EFFECTS_SKILL = 1;
    case KINGDOM_DAMAGE = 2;
    case OTHER = 3;
    case USE_ON_ITEMS = 4;

    public function isStatIncrease(): bool
    {
        return $this === self::STAT_INCREASE;
    }

    public function effectsSkill(): bool
    {
        return $this === self::EFFECTS_SKILL;
    }

    public function damagesKingdom(): bool
    {
        return $this === self::KINGDOM_DAMAGE;
    }

    public function isOther(): bool
    {
        return $this === self::OTHER;
    }

    public function canUseOnItems(): bool
    {
        return $this === self::USE_ON_ITEMS;
    }

    public function getNamedValue(): string
    {
        return match ($this) {
            self::STAT_INCREASE => 'Stat increase',
            self::EFFECTS_SKILL => 'Effects skill',
            self::KINGDOM_DAMAGE => 'Deals damage to a kingdom',
            self::OTHER => 'Effects multiple stats',
            self::USE_ON_ITEMS => 'Use on items.',
        };
    }
}
