<?php

namespace App\Game\Kingdoms\Handlers\Traits;

use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use Illuminate\Database\Eloquent\Collection;

trait AttackHandlerCalculations {

    /**
     * Calculate the total percentage of units or buildings defence lost.
     *
     * We can flip the calculation to be totalAttack and totalDefence depending on what it is
     * we are trying to calculate.
     *
     * @param int $totalAttack
     * @param int $totalDefence
     * @param bool $flipped
     * @return float
     */
    public function calculatePercentageLost(int $totalAttack, int $totalDefence, bool $flipped = false): float {
        if ($totalDefence === 0) {
            return 0.0;
        }

        if (!$flipped) {
            return ($totalAttack / $totalDefence);
        }

        return  ($totalDefence / $totalAttack);
    }

    /**
     * Update the kingdom buildings based on durability percentage lost.
     *
     * This percentage is then divided across all buildings so they take damage equally.
     *
     * @param KingdomBuilding $building
     * @param float $durabilityPercentageLost
     */
    public function updateKingdomBuilding(KingdomBuilding $building, float $durabilityPercentageLost) {
        $durability = ceil($building->current_durability - ($building->current_durability * $durabilityPercentageLost));

        $building->update([
            'current_durability' => $durability < 0 ? 0 : $durability,
        ]);
    }



    /**
     * Get the kingdom buildings total defence.
     *
     * @param Collection $buildings
     * @return int
     */
    public function getKingdomBuildingsTotalDefence(Collection $buildings): int {
        $totalDefence = 0;

        foreach ($buildings as $building) {
            if (!$this->hasKingdomBuildingFallen($building)) {
                $totalDefence += $building->current_defence;
            }
        }

        return $totalDefence;
    }

    /**
     * Gets the new unit total based on the percentage of units lost.
     *
     * @param int $totalUnits
     * @param float $percentageOfUnitsLost
     * @return float
     */
    private function getNewUnitTotal(int $totalUnits, float $percentageOfUnitsLost): float {
        return ceil($totalUnits - ($totalUnits * $percentageOfUnitsLost));
    }

    /**
     * Calculates the new percentage of attackers lost.
     *
     * @param $totalPercentageLost
     * @param array $healerUnits
     * @return float|int
     */
    public function calculateNewPercentageOfAttackersLost($totalPercentageLost, array $healerUnits) {
        $totalPercentageHealed  = $this->totalHealingAmount($healerUnits);

        $totalLost = $totalPercentageLost - $totalPercentageHealed;

        return  ($totalLost > 0) ? $totalLost : 0;
    }

    /**
     * Get the total healing amount.
     *
     * @param array $healingUnits
     * @return float
     */
    public function totalHealingAmount(array $healingUnits): float {
        $healingAmount = 0.00;

        if (empty($healingUnits)) {
            return $healingAmount;
        }

        foreach ($healingUnits as $unit) {
            $healingAmount += $unit['heal_for'];
        }

        return $healingAmount;
    }

    /**
     * Get the total attack for attacking units.
     *
     * @param array $attackingUnits
     * @return int
     */
    public function getTotalAttack(array $attackingUnits): int {
        $totalAttack = 0;

        foreach ($attackingUnits as $unitInfo) {
            $totalAttack += $unitInfo['total_attack'];
        }

        return $totalAttack;
    }

    /**
     * Get the total defence for attacking units.
     *
     * @param array $attackingUnits
     * @return int
     */
    public function getTotalDefence(array $attackingUnits): int {
        $totalDefence = 0;

        foreach ($attackingUnits as $unitInfo) {
            $totalDefence += $unitInfo['total_defence'];
        }

        return $totalDefence;
    }
}
