<?php

namespace App\Game\Kingdoms\Validation;

use App\Flare\Models\Building;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;

class ResourceValidation {

    public function shouldRedirectBuilding(Building $building, Kingdom $kingdom): bool {
        return ($kingdom->current_wood < $building->wood_cost) && 
               ($kingdom->current_clay < $building->clay_cost) &&
               ($kingdom->current_stone < $building->stone_cost) &&
               ($kingdom->current_iron < $building->iron_cost) &&
               ($kingdom->current_population < $building->required_population);
    }

    public function shouldRedirectUnits(GameUnit $unit, Kingdom $kingdom, int $amount): bool {
        return ($kingdom->current_wood < ($unit->wood_cost * $amount)) && 
               ($kingdom->current_clay < ($unit->clay_cost * $amount)) &&
               ($kingdom->current_stone < ($unit->stone_cost * $amount)) &&
               ($kingdom->current_iron < ($unit->iron_cost * $amount)) &&
               ($kingdom->current_population < ($unit->required_population * $amount));
    }
}