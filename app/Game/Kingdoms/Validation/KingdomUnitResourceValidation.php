<?php

namespace App\Game\Kingdoms\Validation;

use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Values\KingdomResources;

class KingdomUnitResourceValidation
{

    /**
     * For the amount of units we want to recruit, are we missing resources?
     *
     * @param Kingdom $kingdom
     * @param GameUnit $gameUnit
     * @param integer $amount
     * @return boolean
     */
    public function isMissingResources(Kingdom $kingdom, GameUnit $gameUnit, int $amount): bool
    {
        $costs = $this->getCostsRequired($kingdom, $gameUnit, $amount);

        foreach ($costs as $resource => $cost) {
            if ($kingdom->{'current_' . $resource} < $cost) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the amount of resources we are missing.
     *
     * @param Kingdom $kingdom
     * @param array $costs
     * @return array
     */
    public function getMissingCosts(Kingdom $kingdom, array $costs): array
    {
        $missingCosts = [];

        foreach ($costs as $resource => $cost) {
            $amountMissing = $cost - $kingdom->{'current_' . $resource};

            if ($amountMissing > 0) {
                $missingCosts[$resource] = $amountMissing;
            }
        }

        return $missingCosts;
    }

    /**
     * Get the cost required to recruit the amount of units for a kingdom
     *
     * @param Kingdom $kingdom
     * @param GameUnit $gameUnit
     * @param integer $amount
     * @return array
     */
    public function getCostsRequired(Kingdom $kingdom, GameUnit $gameUnit, int $amount): array
    {
        $kingdomUnitCostReduction = $kingdom->fetchUnitCostReduction();
        $ironCostReduction = $kingdom->fetchIronCostReduction();

        $costRequired = [];

        foreach (KingdomResources::kingdomResources() as $resourceType) {

            if ($resourceType === KingdomResources::POPULATION->value) {

                $resourceAmountRequired = $gameUnit->{'required_' . $resourceType} * $amount;

                $resourceAmountRequired -= $resourceAmountRequired * $kingdomUnitCostReduction;

                $costRequired[$resourceType] = $resourceAmountRequired;

                continue;
            }

            $resourceAmountRequired = $gameUnit->{$resourceType . '_cost'} * $amount;

            if ($resourceType === KingdomResources::IRON->value) {
                $resourceAmountRequired -= $resourceAmountRequired * ($kingdomUnitCostReduction + $ironCostReduction);

                $costRequired[$resourceType] = $resourceAmountRequired;

                continue;
            }

            $resourceAmountRequired -= $resourceAmountRequired * $kingdomUnitCostReduction;

            $costRequired[$resourceType] = $resourceAmountRequired;
        }

        return $costRequired;
    }
}
