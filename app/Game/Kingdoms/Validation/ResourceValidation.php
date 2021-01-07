<?php

namespace App\Game\Kingdoms\Validation;

use App\Flare\Models\Building;
use App\Flare\Models\Kingdom;

class ResourceValidation {

    public function shouldRedirect(Building $building, Kingdom $kingdom): bool {
        return ($kingdom->current_wood < $building->wood_cost) && 
               ($kingdom->current_clay < $building->clay_cost) &&
               ($kingdom->current_stone < $building->stone_cost) &&
               ($kingdom->current_iron < $building->iron_cost) &&
               ($kingdom->current_population < $building->required_population);
    }
}