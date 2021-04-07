<?php

namespace App\Game\Kingdoms\Handlers;

use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Handlers\Traits\AttackHandler;

use function PHPUnit\Framework\isEmpty;

class UnitHandler {

    use AttackHandler;

    public function attack(Kingdom $defender, array $attackingUnits): array {
        $attackingUnits = $this->handleUnits($defender, $attackingUnits);

        return $attackingUnits;
    }

    protected function handleUnits(Kingdom $defender, array $attackingUnits) {
        $defendingUnits = $defender->units;

        if ($defendingUnits->isEmpty()) {
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

        if ($totalDefenderAttack === 0) {
            return $attackingUnits;
        }

        $defenceBonus = $this->getTotalDefenceBonus($defender);

        $totalDefenderDefence = $totalDefenderDefence * ($defenceBonus > 0 ? $defenceBonus : 1 + $defenceBonus);

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

    private function getTotalDefenceBonus(Kingdom $defender) {
        $totalUnitTypes = $defender->units()->count();
        $totalDefenders = $defender->units()->join('game_units', function($join) {
          $join->on('kingdom_units.game_unit_id', 'game_units.id')->where('game_units.defender', true)->where('kingdom_units.amount', '>', 0);
        })->count();

        $walls          = $defender->buildings->where('is_walls', true)->first();
        $wallsBonus     = 0;

        if ($totalUnitTypes === 0 || $totalDefenders === 0) {
            return 0.0;
        }

        if ($walls->current_durability > 0) {
            $wallsBonus = ($walls->level / 100);
        }

        return ($totalDefenders / $totalUnitTypes) + $wallsBonus;

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
        $percentageLost    = ($percentageLost / count($attackingUnits));
        $oldAttackingUnits = $attackingUnits;

        foreach ($attackingUnits as $index => $unitInfo) {
            if (!$unitInfo['settler']) {
                $amountLost = ceil($unitInfo['amount'] - ($unitInfo['amount'] * $percentageLost));

                $attackingUnits[$index]['amount'] = $amountLost > 0 ? $amountLost : 0;
            }
        }

        return $this->healAttackingUnits($attackingUnits, $oldAttackingUnits, $this->getHealingAmountForAttacking($attackingUnits));
    }

    private function healAttackingUnits(array $attackingUnits, array $oldAttackingUnits, float $healingAmount): array {
        $healingAmount = ($healingAmount / count($attackingUnits));

        foreach ($attackingUnits as $index => $unitInfo) {
            if (!$unitInfo['settler']) {
                $amountHealed = ceil($unitInfo['amount'] * ($healingAmount > 1 ? $healingAmount : (1 + $healingAmount)));

                $oldAmount = $oldAttackingUnits[$index]['amount'];

                $attackingUnits[$index]['amount'] = $amountHealed;

                $newAmount = $attackingUnits[$index]['amount'];

                if ($newAmount > $oldAmount) {
                    $attackingUnits[$index]['amount'] = $oldAttackingUnits;
                }
            }
        }

        return $attackingUnits;
    }

    private function getHealingAmountForAttacking(array $attackingUnits) {
        $totalHealingAmount = 0.00;

        foreach ($attackingUnits as $index => $unitInfo) {
            if ($unitInfo['healer']) {
                $totalHealingAmount += $unitInfo['heal_for'];
            }
        }

        return $totalHealingAmount;
    }

    private function updateDefenderUnitsLeft(Kingdom $defender, float $percentageLost) {
        $totalUnitTypes = $defender->units->count();
        $percentageLost = ($percentageLost / $totalUnitTypes);
        $oldAmounts     = [];

        foreach ($defender->units as $unit) {
            $oldAmounts[$unit->id] = $unit->amount;
            $newAmount = $unit->amount - ($unit->amount * $percentageLost);

            $unit->update([
                'amount' => $newAmount > 0 ? $newAmount : 0,
            ]);

            $unit->refresh();
        }

        $defender = $defender->refresh();

        $this->healDefendingUnits($defender, $oldAmounts, $this->getHealingAmountForDefender($defender));
    }
}
