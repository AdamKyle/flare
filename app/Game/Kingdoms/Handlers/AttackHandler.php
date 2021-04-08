<?php

namespace App\Game\Kingdoms\Handlers;

use Illuminate\Database\Eloquent\Collection;
use App\Game\Kingdoms\Handlers\Traits\DefenderHandler;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Game\Kingdoms\Handlers\Traits\AttackHandlerCalculations;
use App\Game\Kingdoms\Handlers\Traits\SiegeUnits;

class AttackHandler {

    use AttackHandlerCalculations, SiegeUnits, DefenderHandler;

    /**
     * Attack all kingdoms buildings.
     *
     * @param Kingdom $defender
     * @param Collection $targets
     * @param array $unitInfo
     * @param array $healerUnits
     * @return array
     */
    public function attackAllKingdomBuildings(Kingdom $defender, Collection $targets, array $unitInfo, array $healerUnits): array {
        $totalAttack  = $unitInfo['total_attack'];
        $totalDefence = $unitInfo['total_defence'];

        $defenderSiegeUnits               = $this->getDefenderSiegeUnits($defender);
        $defenderSiegeUnitsAttack         = $this->defenderSiegeUnitsAttack($defenderSiegeUnits);
        $defenderKingdomBuildingsDefence  = $this->getKingdomBuildingsTotalDefence($targets);

        if (!$defenderSiegeUnits->isEmpty()) {
            $defenderSiegeUnitsAttack = $this->defenderSiegeUnitsAttack($defenderSiegeUnits);
        }

        if ($totalAttack > $defenderKingdomBuildingsDefence) {

            $totalPercentageDurabilityLost = $this->calculatePerentageLost($totalAttack, $defenderKingdomBuildingsDefence);

            $this->updateAllKingdomBuildings($targets, $totalPercentageDurabilityLost);

            if ($defenderSiegeUnitsAttack !== 0) {
                $totalPercentageOfAttackersLost = $this->calculatePerentageLost($defenderSiegeUnitsAttack, $totalDefence);

                $newUnitTotal       = $this->getNewUnitTotal($unitInfo['amount'], $this->calculateNewPercentageOfAttackersLost($totalPercentageOfAttackersLost, $healerUnits));
                $unitInfo['amount'] = $newUnitTotal > 0 ? $newUnitTotal : 0;
            }
        } else {

            if ($defenderSiegeUnitsAttack !== 0) {
                $totalPercentageOfAttackersLost = $this->calculatePerentageLost($defenderSiegeUnitsAttack, $totalDefence);

                $newUnitTotal       = $this->getNewUnitTotal($unitInfo['amount'], $this->calculateNewPercentageOfAttackersLost($totalPercentageOfAttackersLost, $healerUnits));
                $unitInfo['amount'] = $newUnitTotal > 0 ? $newUnitTotal : 0;
            }
        }

        return $unitInfo;
    }

    /**
     * Attack units.
     *
     * @param Kingdom $defender
     * @param array $unitInfo
     * @param array $healerUnits
     * @return array
     */
    public function unitAttack(Kingdom $defender, array $unitInfo, array $healerUnits) {
        $totalDefenderAttack  = 0;
        $totalDefenderDefence = 0;
        $totalDefenderTypes   = 0;

        $totalAttack          = $unitInfo['total_attack'];
        $totalDefence         = $unitInfo['total_defence'];

        foreach ($defender->units as $unit) {
            $totalDefenderAttack  += $unit->amount * $unit->gameUnit->attack;
            $totalDefenderDefence += $unit->amount * $unit->gameUnit->defence;
            $totalDefenderTypes   += 1;
        }

        if ($totalDefenderAttack === 0) {
            return $unitInfo;
        }

        if ($totalAttack > $totalDefenderDefence) {
            $totalDefenderPercentageLost = $this->calculatePerentageLost($totalAttack, $totalDefenderDefence);
            $totalAttackersLost          = $this->calculatePerentageLost($totalAttack, $totalDefenderDefence, true);

            $this->updateDefenderUnits($defender, $totalDefenderPercentageLost);
            $newUnitTotal = $this->getNewUnitTotal($unitInfo['amount'], $this->calculateNewPercentageOfAttackersLost($totalAttackersLost, $healerUnits));

            $unitInfo['amount'] = $newUnitTotal > 0 ? $newUnitTotal : 0;
        } else {
            $totalDefenderPercentageLost = $this->calculatePerentageLost($totalDefenderAttack, $totalDefence, true);
            $totalAttackersLost          = $this->calculatePerentageLost($totalDefenderAttack, $totalDefence);

            $this->updateDefenderUnits($defender, $totalDefenderPercentageLost);
            $newUnitTotal = $this->getNewUnitTotal($unitInfo['amount'], $this->calculateNewPercentageOfAttackersLost($totalAttackersLost, $healerUnits));

            $unitInfo['amount'] = $newUnitTotal > 0 ? $newUnitTotal : 0;
        }

        return $unitInfo;
    }

    /**
     * Attack specific target.
     *
     * @param KingdomBuilding $target
     * @param array $unitInfo
     * @param array $healerUnits
     * @return array
     */
    public function attackTarget(KingdomBuilding $target, array $unitInfo, array $healerUnits): array {
        $totalAttack = $unitInfo['total_attack'];

        if ($totalAttack > $target->current_defence) {
            $totalPercentageUnitsLost      = $this->calculatePerentageLost($totalAttack, $target->current_defence, true);
            $totalPercentageDurabilityLost = $this->calculatePerentageLost($totalAttack, $target->current_defence);

            $this->updateKingdomBuilding($target, $totalPercentageDurabilityLost);

            $newUnitTotal       = $this->getNewUnitTotal($unitInfo['amount'], $this->calculateNewPercentageOfAttackersLost($totalPercentageUnitsLost, $healerUnits));
            $unitInfo['amount'] = $newUnitTotal > 0 ? $newUnitTotal : 0;
        } else {

            $totalPercentageUnitsLost = $this->calculatePerentageLost($totalAttack, $target->current_defence, true);

            $this->updateKingdomBuilding($target, 0.01);

            $newUnitTotal       = $this->getNewUnitTotal($unitInfo['amount'], $this->calculateNewPercentageOfAttackersLost($totalPercentageUnitsLost, $healerUnits));
            $unitInfo['amount'] = $newUnitTotal > 0 ? $newUnitTotal : 0;
        }

        return $unitInfo;
    }

    /**
     * Attack primary target.
     *
     * @param Kingdom $defender
     * @param array $unitInfo
     * @param array $healers
     * @return array
     */
    public function primaryAttack(Kingdom $defender, array $unitInfo, array $healers): array {
        $primaryTarget = $defender->buildings->where('name', $unitInfo['primary_target'])->first();

        if (is_null($primaryTarget)) {
            return $unitInfo;
        }

        if ($this->hasKingdomBuildingFallen($primaryTarget)) {
            return $unitInfo;
        }

        return $this->attackTarget($primaryTarget, $unitInfo, $healers);
    }

    /**
     * Attacks kingdom building.
     *
     * @param Kingdom $defender
     * @param array $unitInfo
     * @param array $healerUnits
     * @return array
     */
    public function attackKingdomBuildings(Kingdom $defender, array $unitInfo, array $healerUnits): array {
        $buildings = $defender->buildings->where('is_walls', false);

        return $this->attackAllKingdomBuildings($defender, $buildings, $unitInfo, $healerUnits);
    }

    /**
     * Attacks fall back buildings.
     *
     * @param Kingdom $defender
     * @param array $unitInfo
     * @param array $healers
     * @return array
     */
    public function fallBackAttack(Kingdom $defender, array $unitInfo, array $healers): array {
        $fallBackTarget = $defender->buildings->where('name', $unitInfo['fall_back'])->first();

        if (is_null($fallBackTarget)) {
            return $unitInfo;
        }

        if ($this->hasKingdomBuildingFallen($fallBackTarget)) {
            return $unitInfo;
        }

        return $this->attackTarget($fallBackTarget, $unitInfo, $healers);
    }
}
