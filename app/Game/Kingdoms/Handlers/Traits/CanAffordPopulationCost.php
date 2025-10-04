<?php

namespace App\Game\Kingdoms\Handlers\Traits;

use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Values\UnitCosts;

trait CanAffordPopulationCost
{
    /**
     * Determine if the kingdom can afford the population cost.
     */
    private function canAffordPopulationCost(Kingdom $kingdom, int $populationAmount): bool
    {
        if ($kingdom->treasury <= 0) {
            return false;
        }

        $cost = (new UnitCosts(UnitCosts::PERSON))->fetchCost() * $populationAmount;

        return $kingdom->treasury >= $cost;
    }
}
