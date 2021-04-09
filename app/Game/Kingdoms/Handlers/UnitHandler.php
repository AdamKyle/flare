<?php

namespace App\Game\Kingdoms\Handlers;

use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Handlers\Traits\AttackHandlerCalculations;

use App\Game\Kingdoms\Handlers\Traits\DefenderHandler;
use function PHPUnit\Framework\isEmpty;

class UnitHandler {

    use AttackHandlerCalculations, DefenderHandler;

    /**
     * Attack units.
     *
     * @param Kingdom $defender
     * @param array $attackingUnits
     * @return array
     */
    public function attack(Kingdom $defender, array $attackingUnits): array {
        $attackingUnits = $this->handleUnits($defender, $attackingUnits);

        return $attackingUnits;
    }

    /**
     * Handle the attack for the units vs units
     *
     * @param Kingdom $defender
     * @param array $attackingUnits
     * @return array
     */
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

            $attackingUnits = $this->updateAttackingUnits($attackingUnits, $totalAttackingUnitsLost);

            $this->updateDefenderUnits($defender, $totalDefenderUnitsLost);
        } else {
            $totalAttackingUnitsLost = $this->calculatePerentageLost($totalDefenderAttack, $totalAttackingDefence);
            $totalDefenderUnitsLost  = $this->calculatePerentageLost($totalDefenderAttack, $totalAttackingDefence, true);

            $attackingUnits = $this->updateAttackingUnits($attackingUnits, $totalAttackingUnitsLost);

            $this->updateDefenderUnits($defender, $totalDefenderUnitsLost);
        }

        return $attackingUnits;
    }

    /**
     * Update the attacking units.
     *
     * @param array $attackingUnits
     * @param float $percentageLost
     * @return array
     */
    protected function updateAttackingUnits(array $attackingUnits, float $percentageLost): array {
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

    /**
     * Heal the attacking units.
     *
     * @param array $attackingUnits
     * @param array $oldAttackingUnits
     * @param float $healingAmount
     * @return array
     */
    private function healAttackingUnits(array $attackingUnits, array $oldAttackingUnits, float $healingAmount): array {
        $healingAmount = ($healingAmount / count($attackingUnits));

        foreach ($attackingUnits as $index => $unitInfo) {
            if (!$unitInfo['settler']) {
                $amountHealed = ceil($unitInfo['amount'] * ($healingAmount > 1 ? $healingAmount : (1 + $healingAmount)));

                $oldAmount = $oldAttackingUnits[$index]['amount'];

                $attackingUnits[$index]['amount'] = $amountHealed;

                $newAmount = $attackingUnits[$index]['amount'];

                if ($newAmount > $oldAmount) {
                    $attackingUnits[$index]['amount'] = $oldAmount;
                } else {
                    $attackingUnits[$index]['amount'] = $newAmount;
                }
            }
        }

        return $attackingUnits;
    }

    /**
     * Get the total healing amount for attacking units.
     *
     * @param array $attackingUnits
     * @return float|mixed
     */
    private function getHealingAmountForAttacking(array $attackingUnits) {
        $totalHealingAmount = 0.00;

        foreach ($attackingUnits as $index => $unitInfo) {
            if ($unitInfo['healer']) {
                $totalHealingAmount += $unitInfo['heal_for'];
            }
        }

        return $totalHealingAmount;
    }


}
