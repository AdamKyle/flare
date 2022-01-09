<?php


namespace App\Game\Kingdoms\Values;

use App\Flare\Models\KingdomBuilding;

class BuildingActions
{

    const GOBLIN_COIN_BANK         = 'Goblin Coin Bank';

    const GOBLIN_COIN_BANK_LEVEL   = 5;

    public static function canAccessGoblinBank(KingdomBuilding $building): bool {
        return $building->level >= self::GOBLIN_COIN_BANK_LEVEL;
    }
}
