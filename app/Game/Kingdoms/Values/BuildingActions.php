<?php


namespace App\Game\Kingdoms\Values;

use App\Flare\Models\KingdomBuilding;

class BuildingActions
{

    const GOBLIN_COIN_BANK          = 'Goblin Coin Bank';

    const BLACKSMITHS_FURNACE       = 'Blacksmith\'s Furnace';

    const GOBLIN_COIN_BANK_LEVEL    = 5;

    const BLACKSMITHS_FURNACE_LEVEL = 6;

    public static function canAccessGoblinBank(KingdomBuilding $building): bool {
        return $building->level >= self::GOBLIN_COIN_BANK_LEVEL;
    }

    public static function canAccessSmelter(KingdomBuilding $building): bool {
        return $building->level >= self::BLACKSMITHS_FURNACE_LEVEL;
    }
}
