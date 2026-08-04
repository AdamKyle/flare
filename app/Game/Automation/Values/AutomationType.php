<?php

namespace App\Game\Automation\Values;

enum AutomationType: int
{
    case EXPLORING = 0;
    case DELVE = 1;
    case FACTION_LOYALTY = 2;

    public function isExploring(): bool
    {
        return $this === self::EXPLORING;
    }

    public function isDelve(): bool
    {
        return $this === self::DELVE;
    }

    public function isFactionLoyalty(): bool
    {
        return $this === self::FACTION_LOYALTY;
    }
}
