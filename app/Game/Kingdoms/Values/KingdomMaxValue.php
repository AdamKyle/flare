<?php

namespace App\Game\Kingdoms\Values;

use App\Flare\Models\Kingdom;

class KingdomMaxValue
{
    const MAX_TREASURY = 2000000000;

    const MAX_UNIT = 100000;

    const MAX_CURRENT_POPULATION = 250000;

    const MAX_GOLD_BARS = 1000;

    public static function isTreasuryAtMax(Kingdom $kingdom): bool
    {
        return $kingdom->treasury === self::MAX_TREASURY;
    }
}
