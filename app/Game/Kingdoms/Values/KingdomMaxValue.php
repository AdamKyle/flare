<?php

namespace App\Game\Kingdoms\Values;

use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomUnit;

class KingdomMaxValue {

    const MAX_TREASURY = 4000000000;

    const MAX_UNIT = 100000;

    public static function isTreasuryAtMax(Kingdom $kingdom): bool {
        return $kingdom->treasury === self::MAX_TREASURY;
    }
}
