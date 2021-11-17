<?php

namespace App\Game\Kingdoms\Values;

use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomUnit;

class KingdomMaxValue {

    const MAX_TREASURY = 2000000000;

    const MAX_UNIT = 1000000000;

    const MAX_CURRENT_POPULATION = 2000000000;

    public static function isTreasuryAtMax(Kingdom $kingdom): bool {
        return $kingdom->treasury === self::MAX_TREASURY;
    }
}
