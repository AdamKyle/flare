<?php

namespace App\Game\Kingdoms\Validation;

use App\Flare\Models\Building;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;

class ResourceValidation {
    
    /**
     * Do we have enough resources for the building?
     * 
     * @param Building $building
     * @param Kingdom $kingdom
     * @return bool
     */
    public function shouldRedirectBuilding(Building $building, Kingdom $kingdom): bool {
        return ($kingdom->current_wood < $building->wood_cost) && 
               ($kingdom->current_clay < $building->clay_cost) &&
               ($kingdom->current_stone < $building->stone_cost) &&
               ($kingdom->current_iron < $building->iron_cost) &&
               ($kingdom->current_population < $building->required_population);
    }

    public function shouldRedirectRebuildBuilding(Building $building, Kingdom $kingdom): bool {
        return ($kingdom->current_wood < ($building->level * $building->base_wood_cost)) && 
               ($kingdom->current_clay < ($building->level * $building->base_clay_cost)) &&
               ($kingdom->current_stone < ($building->level * $building->base_stone_cost)) &&
               ($kingdom->current_iron < ($building->level * $building->base_iron_cost)) &&
               ($kingdom->current_population < ($building->level * $building->base_population));
    }

    /**
     * Do we have enough resources to recruit the units?
     * 
     * @param GameUnit $unit
     * @param Kingdom $kingdom
     * @param int $amount
     * @return bool
     */
    public function shouldRedirectUnits(GameUnit $unit, Kingdom $kingdom, int $amount): bool {
        return ($kingdom->current_wood < ($unit->wood_cost * $amount)) && 
               ($kingdom->current_clay < ($unit->clay_cost * $amount)) &&
               ($kingdom->current_stone < ($unit->stone_cost * $amount)) &&
               ($kingdom->current_iron < ($unit->iron_cost * $amount)) &&
               ($kingdom->current_population < ($unit->required_population * $amount));
    }
}