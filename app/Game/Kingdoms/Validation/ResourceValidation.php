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
     * Get the missing costs for the building trying to upgrade.
     */
    public function getMissingCosts(KingdomBuilding $building, Kingdom $kingdom): array
    {
        $result = [
            'wood' => $this->calculateResourceDifference($kingdom->current_wood, $this->getBuildingCost($kingdom, $building->base_wood_cost)),
            'clay' => $this->calculateResourceDifference($kingdom->current_clay, $this->getBuildingCost($kingdom, $building->base_clay_cost)),
            'stone' => $this->calculateResourceDifference($kingdom->current_stone, $this->getBuildingCost($kingdom, $building->base_stone_cost)),
            'steel' => $this->calculateResourceDifference($kingdom->current_steel, $this->getBuildingCost($kingdom, $building->steel_cost)),
            'iron' => $this->calculateResourceDifference($kingdom->current_iron, $this->getBuildingCost($kingdom, $building->base_iron_cost, false, true)),
            'population' => $this->calculateResourceDifference($kingdom->current_population, $this->getBuildingCost($kingdom, $building->base_population, true)),
        ];

        $filteredResult = array_filter($result, function ($value) {
            return $value > 0;
        });

        return $filteredResult;

    }

    private function calculateResourceDifference($current, $cost) {
        $difference = $current - $cost;
        return $difference < 0 ? abs($difference) : 0;
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

    public function getMissingUnitResources(GameUnit $unit, Kingdom $kingdom, int $amount): array
    {
        $result = [
            'wood' => $this->calculateResourceDifference($kingdom->current_wood, $this->getUnitCost($kingdom, ($unit->wood_cost * $amount))),
            'clay' => $this->calculateResourceDifference($kingdom->current_clay, $this->getUnitCost($kingdom, ($unit->clay_cost * $amount))),
            'stone' => $this->calculateResourceDifference($kingdom->current_stone, $this->getUnitCost($kingdom, ($unit->stone_cost * $amount))),
            'steel' => $this->calculateResourceDifference($kingdom->current_steel, $this->getUnitCost($kingdom, ($unit->steel_cost * $amount))),
            'iron' => $this->calculateResourceDifference($kingdom->current_iron, $this->getUnitCost($kingdom, ($unit->iron_cost * $amount))),
            'population' => $this->calculateResourceDifference($kingdom->current_population, $this->getUnitCost($kingdom, ($unit->population_cost * $amount), true))
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
