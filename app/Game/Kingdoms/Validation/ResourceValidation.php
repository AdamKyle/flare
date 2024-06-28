<?php

namespace App\Game\Kingdoms\Validation;

use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;

class ResourceValidation {

    /**
     * Do we have enough resources for the building?
     *
     * @param KingdomBuilding $building
     * @param Kingdom $kingdom
     * @return bool
     */
    public function shouldRedirectKingdomBuilding(KingdomBuilding $building, Kingdom $kingdom): bool {
        return ($kingdom->current_wood < $this->getBuildingCost($kingdom, $building->wood_cost)) ||
               ($kingdom->current_clay < $this->getBuildingCost($kingdom, $building->clay_cost)) ||
               ($kingdom->current_stone < $this->getBuildingCost($kingdom, $building->stone_cost)) ||
               ($kingdom->current_steel < $this->getBuildingCost($kingdom, $building->steel_cost)) ||
               (($kingdom->current_iron < $this->getBuildingCost($kingdom, $building->iron_cost, false, true))) ||
               ($kingdom->current_population < $this->getBuildingCost($kingdom, $building->required_population, true));
    }

    /**
     * Can we afford to rebuild?
     *
     * @param KingdomBuilding $building
     * @param Kingdom $kingdom
     * @return bool
     */
    public function shouldRedirectRebuildKingdomBuilding(KingdomBuilding $building, Kingdom $kingdom): bool {
        return ($kingdom->current_wood < $this->getBuildingCost($kingdom, $building->base_wood_cost)) ||
               ($kingdom->current_clay < $this->getBuildingCost($kingdom, $building->base_clay_cost)) ||
               ($kingdom->current_stone < $this->getBuildingCost($kingdom, $building->base_stone_cost)) ||
               ($kingdom->current_steel < $this->getBuildingCost($kingdom, $building->steel_cost)) ||
               ($kingdom->current_iron < $this->getBuildingCost($kingdom, $building->base_iron_cost, false, true)) ||
               ($kingdom->current_population < $this->getBuildingCost($kingdom, $building->base_population, true));
    }

    /**
     * Get the missing costs for the building trying to upgrade.
     *
     * @param KingdomBuilding $building
     * @param Kingdom $kingdom
     * @return array
     */
    public function getMissingCosts(KingdomBuilding $building, Kingdom $kingdom): array {

        return [
            'wood' => abs($kingdom->current_wood - $this->getBuildingCost($kingdom, $building->base_wood_cost)),
            'clay' => abs($kingdom->current_clay - $this->getBuildingCost($kingdom, $building->base_clay_cost)),
            'stone' => abs($kingdom->current_stone - $this->getBuildingCost($kingdom, $building->base_stone_cost)),
            'steel' => abs($kingdom->current_steel - $this->getBuildingCost($kingdom, $building->steel_cost)),
            'iron' => abs($kingdom->current_iron - $this->getBuildingCost($kingdom, $building->base_iron_cost, false, true)),
            'population' => abs($kingdom->current_population - $this->getBuildingCost($kingdom, $building->base_population, true)),
        ];
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
        return ($kingdom->current_wood < $this->getUnitCost($kingdom, ($unit->wood_cost * $amount))) ||
               ($kingdom->current_clay < $this->getUnitCost($kingdom, ($unit->clay_cost * $amount))) ||
               ($kingdom->current_stone < $this->getUnitCost($kingdom, ($unit->stone_cost * $amount))) ||
               ($kingdom->current_steel < $this->getBuildingCost($kingdom, ($unit->steel_cost * $amount))) ||
               ($kingdom->current_iron < $this->getUnitCost($kingdom, ($unit->iron_cost * $amount), false, true)) ||
               ($kingdom->current_population < $this->getUnitCost($kingdom, ($unit->required_population * $amount), true));
    }

    public function getMissingResources(GameUnit $unit, Kingdom $kingdom, int $amount): array {
        return [
            'wood' => $this->getUnitCost($kingdom, ($unit->wood_cost * $amount)) - $kingdom->current_wood,
            'clay' => $this->getUnitCost($kingdom, ($unit->clay_cost * $amount)) - $kingdom->current_clay,
            'stone' => $this->getUnitCost($kingdom, ($unit->stone_cost * $amount)) - $kingdom->current_stone,
            'steel' => $this->getUnitCost($kingdom, ($unit->steel_cost * $amount)) - $kingdom->current_steel,
            'iron' => $this->getUnitCost($kingdom, ($unit->iron_cost * $amount)) - $kingdom->current_iron,
        ];
    }

    /**
     * Fetch the real cost of the units.
     *
     * @param Kingdom $kingdom
     * @param $cost
     * @param bool $isPopulation
     * @param bool $isIron
     * @return int
     */
    protected function getUnitCost(Kingdom $kingdom, $cost, bool $isPopulation = false, bool $isIron = false): int {
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
     *
     * @param Kingdom $kingdom
     * @param int $cost
     * @param bool $isPopulation
     * @param bool $isIron
     * @return int
     */
    protected function getBuildingCost(Kingdom $kingdom, int $cost, bool $isPopulation = false, bool $isIron = false): int {
        if ($isIron) {
            return $cost - $cost * $kingdom->fetchIronCostReduction();
        }

        if ($isPopulation) {
            return $cost - $cost * $kingdom->fetchPopulationCostReduction();
        }

        return $cost - $cost * $kingdom->fetchBuildingCostReduction();
    }
}
