<?php

namespace App\Game\Kingdoms\Validation;

use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;

class ResourceValidation
{
    /**
     * Do we have enough resources for the building?
     */
    public function shouldRedirectKingdomBuilding(KingdomBuilding $building, Kingdom $kingdom): bool
    {

        return ($kingdom->current_wood < $this->getBuildingCost($kingdom, $building->wood_cost)) ||
               ($kingdom->current_clay < $this->getBuildingCost($kingdom, $building->clay_cost)) ||
               ($kingdom->current_stone < $this->getBuildingCost($kingdom, $building->stone_cost)) ||
               ($kingdom->current_steel < $this->getBuildingCost($kingdom, $building->steel_cost)) ||
               (($kingdom->current_iron < $this->getBuildingCost($kingdom, $building->iron_cost, false, true))) ||
               ($kingdom->current_population < $this->getBuildingCost($kingdom, $building->required_population, true));
    }

    /**
     * Can we afford to rebuild?
     */
    public function shouldRedirectRebuildKingdomBuilding(KingdomBuilding $building, Kingdom $kingdom): bool
    {
        return ($kingdom->current_wood < $this->getBuildingCost($kingdom, $building->base_wood_cost)) ||
               ($kingdom->current_clay < $this->getBuildingCost($kingdom, $building->base_clay_cost)) ||
               ($kingdom->current_stone < $this->getBuildingCost($kingdom, $building->base_stone_cost)) ||
               ($kingdom->current_steel < $this->getBuildingCost($kingdom, $building->steel_cost)) ||
               ($kingdom->current_iron < $this->getBuildingCost($kingdom, $building->base_iron_cost, false, true)) ||
               ($kingdom->current_population < $this->getBuildingCost($kingdom, $building->base_population, true));
    }

    /**
     * Get the missing costs for the building trying to upgrade.
     */
    public function getMissingCosts(KingdomBuilding $building, Kingdom $kingdom): array
    {

        $result = [
            'wood' => max($kingdom->current_wood - $this->getBuildingCost($kingdom, $building->base_wood_cost), 0),
            'clay' => max($kingdom->current_clay - $this->getBuildingCost($kingdom, $building->base_clay_cost), 0),
            'stone' => max($kingdom->current_stone - $this->getBuildingCost($kingdom, $building->base_stone_cost), 0),
            'steel' => max($kingdom->current_steel - $this->getBuildingCost($kingdom, $building->steel_cost), 0),
            'iron' => max($kingdom->current_iron - $this->getBuildingCost($kingdom, $building->base_iron_cost, false, true), 0),
            'population' => max($kingdom->current_population - $this->getBuildingCost($kingdom, $building->base_population, true), 0),
        ];

        $filteredResult = array_filter($result, function ($value) {
            return $value > 0;
        });

        return $filteredResult;

    }

    /**
     * Do we have enough resources to recruit the units?
     */
    public function shouldRedirectUnits(GameUnit $unit, Kingdom $kingdom, int $amount): bool
    {
        return ($kingdom->current_wood < $this->getUnitCost($kingdom, ($unit->wood_cost * $amount))) ||
               ($kingdom->current_clay < $this->getUnitCost($kingdom, ($unit->clay_cost * $amount))) ||
               ($kingdom->current_stone < $this->getUnitCost($kingdom, ($unit->stone_cost * $amount))) ||
               ($kingdom->current_steel < $this->getBuildingCost($kingdom, ($unit->steel_cost * $amount))) ||
               ($kingdom->current_iron < $this->getUnitCost($kingdom, ($unit->iron_cost * $amount), false, true)) ||
               ($kingdom->current_population < $this->getUnitCost($kingdom, ($unit->required_population * $amount), true));
    }

    public function getMissingResources(GameUnit $unit, Kingdom $kingdom, int $amount): array
    {
        $result = [
            'wood' => abs($kingdom->current_wood - $this->getUnitCost($kingdom, ($unit->wood_cost * $amount))),
            'clay' => abs($kingdom->current_clay - $this->getUnitCost($kingdom, ($unit->clay_cost * $amount))),
            'stone' => abs($kingdom->current_stone - $this->getUnitCost($kingdom, ($unit->stone_cost * $amount))),
            'steel' => abs($kingdom->current_steel - $this->getUnitCost($kingdom, ($unit->steel_cost * $amount))),
            'iron' => abs($kingdom->current_iron - $this->getUnitCost($kingdom, ($unit->iron_cost * $amount))),
        ];

        $filteredResult = array_filter($result, function ($value) {
            return $value > 0;
        });

        return $filteredResult;

    }

    /**
     * Fetch the real cost of the units.
     */
    protected function getUnitCost(Kingdom $kingdom, $cost, bool $isPopulation = false, bool $isIron = false): int
    {
        if ($isIron) {
            $cost = $cost - $cost * $kingdom->fetchIronCostReduction();
        }

        if ($isPopulation) {
            $cost = $cost - $cost * $kingdom->fetchPopulationCostReduction();
        }

        return $cost - $cost * $kingdom->fetchUnitCostReduction();
    }

    /**
     * Get the actual cost with all modifiers.
     */
    protected function getBuildingCost(Kingdom $kingdom, int $cost, bool $isPopulation = false, bool $isIron = false): int
    {
        if ($isIron) {
            return $cost - $cost * ($kingdom->fetchIronCostReduction() + $kingdom->fetchBuildingCostReduction());
        }

        if ($isPopulation) {
            return $cost - $cost * $kingdom->fetchPopulationCostReduction();
        }

        return $cost - $cost * $kingdom->fetchBuildingCostReduction();
    }
}
