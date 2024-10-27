<?php

namespace App\Game\Kingdoms\Validation;

use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;

class ResourceValidation
{
    /**
     * Check if the kingdom has enough resources for a building or unit.
     */
    public function hasEnoughResources(array $requiredResources, Kingdom $kingdom): bool
    {
        foreach ($requiredResources as $resourceType => $requiredAmount) {
            $currentAmount = $kingdom->{'current_' . $resourceType};
            if ($currentAmount < $requiredAmount) {
                return false;
            }
        }
        return true;
    }

    /**
     * Calculate missing resources if the kingdom lacks enough for a building.
     */
    public function getMissingResources(array $requiredResources, Kingdom $kingdom): array
    {
        $missingResources = [];

        foreach ($requiredResources as $resourceType => $requiredAmount) {
            $currentAmount = $kingdom->{'current_' . $resourceType};
            $difference = $requiredAmount - $currentAmount;

            if ($difference > 0) {
                $missingResources[$resourceType] = $difference;
            }
        }

        return $missingResources;
    }

    /**
     * Determine if we need to redirect due to insufficient resources for a building.
     */
    public function shouldRedirectKingdomBuilding(KingdomBuilding $building, Kingdom $kingdom): bool
    {
        $requiredResources = $this->getBuildingCosts($building, $kingdom);
        return !$this->hasEnoughResources($requiredResources, $kingdom);
    }

    /**
     * Determine missing costs for a building upgrade.
     */
    public function getMissingBuildingCosts(KingdomBuilding $building, Kingdom $kingdom): array
    {
        $requiredResources = $this->getBuildingCosts($building, $kingdom);
        return $this->getMissingResources($requiredResources, $kingdom);
    }

    /**
     * Get building costs with modifiers applied.
     */
    public function getBuildingCosts(KingdomBuilding $building, Kingdom $kingdom): array
    {
        return [
            'wood' => $this->applyModifiers($building->wood_cost, $kingdom),
            'clay' => $this->applyModifiers($building->clay_cost, $kingdom),
            'stone' => $this->applyModifiers($building->stone_cost, $kingdom),
            'iron' => $this->applyModifiers($building->iron_cost, $kingdom, isIron: true),
            'steel' => $this->applyModifiers($building->steel_cost, $kingdom),
            'population' => $this->applyModifiers($building->required_population, $kingdom, isPopulation: true),
        ];
    }

    /**
     * Determine if we need to redirect due to insufficient resources for units.
     */
    public function shouldRedirectUnits(GameUnit $unit, Kingdom $kingdom, int $amount): bool
    {
        $requiredResources = $this->getUnitCosts($unit, $kingdom, $amount);
        return !$this->hasEnoughResources($requiredResources, $kingdom);
    }

    /**
     * Get missing resources for units.
     */
    public function getMissingUnitResources(GameUnit $unit, Kingdom $kingdom, int $amount): array
    {
        $requiredResources = $this->getUnitCosts($unit, $kingdom, $amount);
        return $this->getMissingResources($requiredResources, $kingdom);
    }

    /**
     * Get unit costs with modifiers applied.
     */
    public function getUnitCosts(GameUnit $unit, Kingdom $kingdom, int $amount): array
    {
        return [
            'wood' => $this->applyModifiers($unit->wood_cost * $amount, $kingdom),
            'clay' => $this->applyModifiers($unit->clay_cost * $amount, $kingdom),
            'stone' => $this->applyModifiers($unit->stone_cost * $amount, $kingdom),
            'iron' => $this->applyModifiers($unit->iron_cost * $amount, $kingdom, isIron: true),
            'steel' => $this->applyModifiers($unit->steel_cost * $amount, $kingdom),
            'population' => $this->applyModifiers($unit->population_cost * $amount, $kingdom, isPopulation: true),
        ];
    }

    /**
     * Apply cost reductions based on modifiers from the kingdom.
     */
    private function applyModifiers(int $cost, Kingdom $kingdom, bool $isPopulation = false, bool $isIron = false): int
    {
        if ($isIron) {
            $cost -= $cost * $kingdom->fetchIronCostReduction();
        }

        if ($isPopulation) {
            $cost -= $cost * $kingdom->fetchPopulationCostReduction();
        }

        return $cost - ($cost * $kingdom->fetchBuildingCostReduction());
    }
}
