<?php

namespace App\Game\Kingdoms\Handlers;

use App\Flare\Models\Building;
use App\Flare\Models\Kingdom;
use Illuminate\Database\Eloquent\Collection;

class SiegeHandler {

    public function attack(Kingdom $defender, array $siegeUnits) {

        foreach ($siegeUnits as $index => $unitInfo) {
            $unitInfo = $this->primaryAttack($defender, $unitInfo);
            
            if ($unitInfo['amount'] > 0) {
                if ($unitInfo['fall_back'] === 'Buildings') {
                    $unitInfo = $this->attackBuildings($defender, $unitInfo);
                } else {
                    $unitInfo = $this->fallBackAttack($defender, $unitInfo);
                }
                
            }

            if ($unitInfo['amount'] > 0) {
                $unitInfo = $this->unitAttack($defender, $unitInfo);
            }

            $siegeUnits[$index] = $unitInfo;
        }

        return $siegeUnits;
    }

    protected function primaryAttack(Kingdom $defender, array $unitInfo): array {
        $primaryTarget = $defender->buildings->where('name', $unitInfo['primary_target'])->first();
        
        if (is_null($primaryTarget)) {
            return $unitInfo;
        }
        
        if ($this->hasBuildingFallen($primaryTarget)) {
            return $unitInfo;
        }

        return $this->attackTarget($primaryTarget, $unitInfo);
    }

    protected function fallBackAttack(Kingdom $defender, array $unitInfo): array {
        $fallBackTarget = $defender->buildings->where('name', $unitInfo['fall_back'])->first();

        if (is_null($fallBackTarget)) {
            return $unitInfo;
        }
        
        if ($this->hasBuildingFallen($fallBackTarget)) {
            return $unitInfo;
        }

        return $this->attackTarget($fallBackTarget, $unitInfo);
    }

    protected function attackBuildings(Kingdom $defender, array $unitInfo): array {
        $buildings = $defender->buildings->where('is_walls', false);

        $unitInfo = $this->attackAllBuildings($defender, $buildings, $unitInfo);
    }

    protected function unitAttack(Kingdom $defender, array $unitInfo) {
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

        if ($totalAttack > $totalDefenderDefence) {
            $totalDefenderPercentageLost = $this->calculatePerentageLost($totalAttack, $totalDefenderDefence);
            $totalAttackersLost          = $this->calculatePerentageLost($totalAttack, $totalDefenderDefence, true);

            $this->updateDefenderUnits($defender, $totalDefenderPercentageLost);
            $newUnitTotal = $this->getNewUnitTotal($unitInfo['amount'], $totalAttackersLost);

            $unitInfo['amount'] = $newUnitTotal > 0 ? $newUnitTotal : 0;
        } else {
            $totalDefenderPercentageLost = $this->calculatePerentageLost($totalDefenderAttack, $totalDefence, true);
            $totalAttackersLost          = $this->calculatePerentageLost($totalDefenderAttack, $totalDefence);

            $this->updateDefenderUnits($defender, $totalDefenderPercentageLost);
            $newUnitTotal = $this->getNewUnitTotal($unitInfo['amount'], $totalAttackersLost);

            $unitInfo['amount'] = $newUnitTotal > 0 ? $newUnitTotal : 0;
        }
        
        return $unitInfo;
    }

    private function attackTarget(Building $target, array $unitInfo): array {
        $totalAttack = $unitInfo['total_attack'];

        if ($totalAttack > $target->current_defence) {
            $totalPercentageUnitsLost      = $this->calculatePerentageLost($totalAttack, $target->current_defence, true);
            $totalPercentageDurabilityLost = $this->calculatePerentageLost($totalAttack, $target->current_defence);

            $this->updateBuilding($target, $totalPercentageDurabilityLost);

            $newUnitTotal       = $this->getNewUnitTotal($unitInfo['amount'], $totalPercentageUnitsLost);
            $unitInfo['amount'] = $newUnitTotal > 0 ? $newUnitTotal : 0;
        } else {
            
            $totalPercentageUnitsLost = $this->calculatePerentageLost($totalAttack, $target->current_defence, true);

            $this->updateBuilding($target, 0.01);
            
            $newUnitTotal       = $this->getNewUnitTotal($unitInfo['amount'], $totalPercentageUnitsLost);
            $unitInfo['amount'] = $newUnitTotal > 0 ? $newUnitTotal : 0;
        }

        return $unitInfo;
    }

    private function attackAllBuildings(Kingdom $defender, Collection $targets, $unitInfo): array {
        $totalAttack  = $unitInfo['total_attack'];
        $totalDefence = $unitInfo['total_defence'];

        $defenderSiegeUnits        = $this->getDefenderSiegeUnits($defender);
        $defenderSiegeUnitsAttack  = 0;
        $defenderBuildingsDefence  = $this->getBuildingsTotalDefence($targets);

        if (!$defenderSiegeUnits->isEmpty()) {
            $defenderSiegeUnitsAttack = $this->defenderSiegeUnitsAttack($defenderSiegeUnits);
        }

        if ($totalAttack > $defenderBuildingsDefence) {
            $totalPercentageDurabilityLost = $this->calculatePerentageLost($totalAttack, $defenderBuildingsDefence);

            $this->updateAllBuildings($targets, $totalPercentageDurabilityLost);

            if ($defenderSiegeUnitsAttack !== 0) {
                $totalPercentageOfAttackersLost = $this->calculatePerentageLost($defenderSiegeUnitsAttack, $totalDefence);

                $newUnitTotal       = $this->getNewUnitTotal($unitInfo['amount'], $totalPercentageOfAttackersLost);
                $unitInfo['amount'] = $newUnitTotal > 0 ? $newUnitTotal : 0;
            }
        } else {
            if ($defenderSiegeUnitsAttack !== 0) {
                $totalPercentageOfAttackersLost = $this->calculatePerentageLost($defenderSiegeUnitsAttack, $totalDefence);

                $newUnitTotal       = $this->getNewUnitTotal($unitInfo['amount'], $totalPercentageOfAttackersLost);
                $unitInfo['amount'] = $newUnitTotal > 0 ? $newUnitTotal : 0;
            }
        }

        return $unitInfo;
    }

    private function calculatePerentageLost(int $totalAttack, int $totalDefence, bool $flipped = false): float {
        if (!$flipped) {
            return ($totalAttack / $totalDefence);
        }

        return  ($totalDefence / $totalAttack);
    }

    private function updateBuilding(Building $building, float $durabilityPercentageLost) {
        $durability = ceil($building->current_durability - ($building->current_durability * $durabilityPercentageLost));

        $building->update([
            'current_durability' => $durability < 0 ? 0 : $durability,
        ]);
    }

    private function updateAllBuildings(Collection $buildings, float $percentageOfDurabilityLost) {
        $buildingsStillStanding = $buildings->where('current_durability', '!=', 0)->all();
        $totalBuildings         = count($buildingsStillStanding);
        $percentageLost         = ($percentageOfDurabilityLost / $totalBuildings);

        foreach ($buildingsStillStanding as $building) {
            $newDurability = $building->current_durability - ($building->current_durability * $percentageLost);

            $building->update([
                'current_durability' => $newDurability > 0 ? $newDurability : 0,
            ]);
        }
    }

    private function updateDefenderUnits(Kingdom $defender, float $percentageOfUnitsLost) {
      foreach ($defender->units as $unit) {
          $newAmount = $this->getNewUnitTotal($unit->amount, $percentageOfUnitsLost);

          $unit->update([
              'amount' => $newAmount > 0 ? $newAmount : 0,
          ]);
      }
    }

    private function getNewUnitTotal(int $totalUnits, float $percentageOfUnitsLost) {
        return ceil($totalUnits - ($totalUnits * $percentageOfUnitsLost));
    }

    private function hasBuildingFallen(Building $building): bool {
        return $building->durabilityPercentageLost > 0;
    }

    private function getDefenderSiegeUnits(Kingdom $defender) {
        return $defender->units()->join('game_units', function($join) {
            $join->on('game_units.id', 'kingdom_units.game_unit_id')
                 ->where('siege_weapon', true)
                 ->where('defender', true);
        })->get();
    }

    private function defenderSiegeUnitsAttack(Collection $siegeUnits): int {
        $totalAttack = 0;

        foreach ($siegeUnits as $siegeUnit) {
            $totalAttack += $siegeUnit->gameUnit->attack * $siegeUnit->amount;
        }

        return $totalAttack;
    }

    private function getBuildingsTotalDefence(Collection $buildings): int {
        $totalDefence = 0;

        foreach ($buildings as $building) {
            if (!$this->hasBuildingFallen($building)) {
                $totalDefence += $building->current_defence;
            }
        }

        return $totalDefence;
    }
}