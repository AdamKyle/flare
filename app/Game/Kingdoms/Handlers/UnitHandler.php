<?php

namespace App\Game\Kingdoms\Handlers;

use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Handlers\Traits\AttackHandler;

use function PHPUnit\Framework\isEmpty;

class UnitHandler {

    use AttackHandler;

    public function attack(Kingdom $defender, array $attackingUnits): array {
        $attackingUnits = $this->handleWalls($defender, $attackingUnits);
        $attackingUnits = $this->handleUnits($defender, $attackingUnits);

        return $attackingUnits;
    }

    public function handleWalls(Kingdom $defender, array $attackingUnits): array {
        $walls = $defender->buildings->where('is_walls', true)->first();

        if (is_null($walls)) {
            return $attackingUnits;
        }

        $totalAttack      = $this->getTotalAttack($attackingUnits);
        $totalWallDefence = $walls->current_defence;

        if ($totalAttack === 0) {
            return $attackingUnits;
        }

        if ($totalAttack > $totalWallDefence) {
            $totalAttackersLost      = $this->calculatePerentageLost($totalAttack, $totalWallDefence, true);
            $totalWallDurabilityLost = $this->calculatePerentageLost($totalAttack, $totalWallDefence);

            $this->updateKingdomBuilding($walls, $totalWallDurabilityLost);
            $attackingUnits = $this->updateUnits($attackingUnits, $totalAttackersLost);
        } else {
            $totalAttackersLost      = $this->calculatePerentageLost($totalAttack, $totalWallDefence, true);

            $this->updateKingdomBuilding($walls, 0.01);

            $attackingUnits = $this->updateUnits($attackingUnits, $totalAttackersLost);
        }

        return $attackingUnits;
    }

    protected function handleUnits(Kingdom $defender, array $attackingUnits) {
        $defendingUnits = $defender->units;

        if (!isEmpty($defendingUnits)) {
            return $attackingUnits;
        }

        $totalAttackingDefence = $this->getTotalDefence($attackingUnits);
        $totalAttack           = $this->getTotalAttack($attackingUnits);

        if ($totalAttack === 0) {
            return $attackingUnits;
        }

        $totalDefenderAttack   = 0;
        $totalDefenderDefence  = 0;
        
        foreach ($defendingUnits as $unit) {
            $totalDefenderAttack  += ($unit->amount) * $unit->gameUnit->attack;
            $totalDefenderDefence += ($unit->amount) * $unit->gameUnit->defence;
        }

        if ($totalAttack > $totalDefenderDefence) {
            $totalAttackingUnitsLost = $this->calculatePerentageLost($totalAttack, $totalDefenderDefence, true);
            $totalDefenderUnitsLost  = $this->calculatePerentageLost($totalAttack, $totalDefenderDefence);

            $atackingUnits = $this->updateUnits($attackingUnits, $totalAttackingUnitsLost);

            $this->updateDefenderUnitsLeft($defender, $totalDefenderUnitsLost);
        } else {
            $totalAttackingUnitsLost = $this->calculatePerentageLost($totalDefenderAttack, $totalAttackingDefence, true);
            $totalDefenderUnitsLost  = $this->calculatePerentageLost($totalDefenderAttack, $totalAttackingDefence);

            $atackingUnits = $this->updateUnits($attackingUnits, $totalAttackingUnitsLost);

            $this->updateDefenderUnitsLeft($defender, $totalDefenderUnitsLost);
        }

        return $atackingUnits;
    }

    private function getTotalAttack(array $attackingUnits): int {
        $totalAttack = 0;

        foreach ($attackingUnits as $unitInfo) {
            $totalAttack += $unitInfo['total_attack'];
        }

        return $totalAttack;
    }

    private function getTotalDefence(array $attackingUnits): int {
        $totalDefence = 0;

        foreach ($attackingUnits as $unitInfo) {
            $totalDefence += $unitInfo['total_defence'];
        }

        return $totalDefence;
    }

    private function updateUnits(array $attackingUnits, float $percentageLost): array {
        $percentageLost = ($percentageLost / count($attackingUnits));

        foreach ($attackingUnits as $index => $unitInfo) {
            $amountLost = ceil($unitInfo['amount'] - ($unitInfo['amount'] * $percentageLost));
            
            $attackingUnits[$index]['amount'] = $amountLost > 0 ? $amountLost : 0;
        }

        return $attackingUnits;
    }
    
    private function updateDefenderUnitsLeft(Kingdom $defender, float $percentageLost) {
        $totalUnitTypes = $defender->units->count();
        $percentageLost = ($percentageLost / $totalUnitTypes);

        foreach ($defender->units as $unit) {
            $newAmount = $unit->amount - ($unit->amount * $percentageLost);
            
            $unit->update([
                'ammount' => $newAmount > 0 ? $newAmount : 0,
            ]);
        }
    }
}