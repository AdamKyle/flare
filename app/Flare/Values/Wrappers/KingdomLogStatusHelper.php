<?php

namespace App\Flare\Values\Wrappers;

use App\Flare\Values\KingdomLogStatusValue;

class KingdomLogStatusHelper {

    /**
     * Wrapper around the the KingdomLogStatusValue
     *
     * @param string $status
     * @return KingdomLogStatusValue
     * @throws \Exception
     */
    public static function statusType(string $status): KingdomLogStatusValue {
        return new KingdomLogStatusValue($status);
    }
}
