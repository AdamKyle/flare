<?php


namespace App\Game\Kingdoms\Values;

use App\Flare\Models\KingdomBuilding;

class BuildingExpansionTypes
{

    const RESOURCE_EXPANSION = 0;

    public function isResourceExpansion(int $type): boolean {
        return $type === self::RESOURCE_EXPANSION;
    }
}
