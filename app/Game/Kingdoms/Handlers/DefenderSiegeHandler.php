<?php

namespace App\Game\Kingdoms\Handlers;

use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Values\UnitNames;

class DefenderSiegeHandler extends BaseDefenderHandler
{
    /**
     * Attack units with siege weapons.
     *
     * - Does not attack with rams. Cannons and Trebuchets only.
     *
     * @return void
     */
    public function attackUnitsWithSiegeWeapons(Kingdom $kingdom)
    {
        $attack = $this->getSiegeAttack($kingdom);
        $amount = $this->getTotalAmountOfUnits();

        if ($attack <= 0 || $amount <= 0) {
            return;
        }

        $attack = $amount / $attack;

        if ($attack <= 0) {
            return;
        }

        if ($attack > 1) {
            $attack = 1;
        }

        $this->updateAttackingUnits($attack);
    }

    /**
     * Get the total siege weapon attack.
     *
     * - Ignore Rams.
     */
    protected function getSiegeAttack(Kingdom $kingdom): int
    {
        $attack = 0;

        foreach ($kingdom->units as $unit) {
            if ($unit->gameUnit->siege_weapon && $unit->gameUnit->name !== UnitNames::RAM && $unit->amount > 0) {
                $attack += $unit->amount * $unit->gameUnit->attack;
            }
        }

        return $attack;
    }
}
