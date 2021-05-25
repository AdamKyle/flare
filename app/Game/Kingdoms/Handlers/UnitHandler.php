<?php

namespace App\Game\Kingdoms\Handlers;

use App\Flare\Models\GameUnit;
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
     * Gets the regular non siege units.
     *
     * @param array $attackingUnits
     * @return array
     */
    public function getRegularUnits(array $attackingUnits): array {
        $regularUnits = [];

        forEach($attackingUnits as $unitInfo) {
            $gameUnit = GameUnit::where('id', $unitInfo['unit_id'])->where('siege_weapon', false)->first();

            if (!is_null($gameUnit)) {
                $regularUnits[] = [
                    'amount'           => $unitInfo['amount'],
                    'total_attack'     => $gameUnit->attack * $unitInfo['amount'],
                    'total_defence'    => $gameUnit->defence * $unitInfo['amount'],
                    'primary_target'   => $gameUnit->primary_target,
                    'fall_back'        => $gameUnit->fall_back,
                    'unit_id'          => $gameUnit->id,
                    'healer'           => $gameUnit->can_heal,
                    'heal_for'         => !is_null($gameUnit->heal_percentage) ? $gameUnit->heal_percentage * $unitInfo['amount'] : 0,
                    'can_be_healed'    => !$gameUnit->can_not_be_healed,
                    'settler'          => $gameUnit->is_settler,
                    'time_to_return'   => $unitInfo['time_to_return'],
                ];
            }
        }

        return $regularUnits;
    }

    /**
     * Fetches the healer units.
     *
     * This is called when sending siege units into battle.
     *
     * @param array $attackingUnits
     * @return array
     */
    public function fetchHealers(array $attackingUnits): array {
        $healerUnits = [];

        foreach ($attackingUnits as $unitInfo) {
            $gameUnit = GameUnit::where('id', $unitInfo['unit_id'])->where('can_heal', true)->first();

            if (is_null($gameUnit)) {
                continue;
            }

            $healerUnits[] = [
                'amount'   => $unitInfo['amount'],
                'heal_for' => $gameUnit->heal_percentage * $unitInfo['amount'],
                'unit_id'  => $gameUnit->id,
            ];
        }

        return $healerUnits;
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
        dump($defenceBonus);
        $totalDefenderDefence = $totalDefenderDefence * ($defenceBonus > 1 ? $defenceBonus : 1 + $defenceBonus);
        dump($totalDefenderDefence);
        if ($totalAttack > $totalDefenderDefence) {
            dump('greator then');
            $totalAttackingUnitsLost = $this->calculatePerentageLost($totalAttack, $totalDefenderDefence, true);
            $totalDefenderUnitsLost  = $this->calculatePerentageLost($totalAttack, $totalDefenderDefence);

            $attackingUnits = $this->updateAttackingUnits($attackingUnits, $totalAttackingUnitsLost);

            $this->updateDefenderUnits($defender, $totalDefenderUnitsLost);
        } else {
            dump('less then');
            $totalAttackingUnitsLost = $this->calculatePerentageLost($totalAttackingDefence, $totalDefenderAttack, true);
            $totalDefenderUnitsLost  = $this->calculatePerentageLost($totalAttackingDefence, $totalDefenderAttack);

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

        $percentageLost = $percentageLost - $this->getHealingAmountForAttacking($attackingUnits);

        if ($percentageLost < 0.0) {
            $percentageLost = 0.0;
        }

        foreach ($attackingUnits as $index => $unitInfo) {
            if (!$unitInfo['settler']) {
                if ($percentageLost !== 0.0) {
                    $amountLost = ceil($unitInfo['amount'] - ($unitInfo['amount'] * $percentageLost));

                    $attackingUnits[$index]['amount'] = $amountLost > 0 ? $amountLost : 0;
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
                $totalHealingAmount += $unitInfo['heal_for'] * $unitInfo['amount'];
            }
        }

        return $totalHealingAmount;
    }
}
