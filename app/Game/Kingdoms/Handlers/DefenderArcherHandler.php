<?php

namespace App\Game\Kingdoms\Handlers;

use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Values\UnitNames;

class DefenderArcherHandler extends BaseDefenderHandler
{
    /**
     * The defenders archers will attack the attackers units.
     *
     * - Only Archers and Mounted Archers will attack.
     * - When updating the attacker units, we ignore the siege weapons.
     *
     * @return void
     */
    public function attackUnitsWithArcherUnits(Kingdom $kingdom)
    {
        $attack = $this->getArcherAttack($kingdom);
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

        $this->updateAttackingUnits($attack, true);
    }

    /**
     * Get the archer attack.
     *
     * - Only gets the Mounted Archer and Archers.
     */
    protected function getArcherAttack(Kingdom $kingdom): int
    {
        $attack = 0;

        foreach ($kingdom->units as $unit) {
            if ($unit->gameUnit->name === UnitNames::MOUNTED_ARCHERS || $unit->gameUnit->name === UnitNames::ARCHER) {
                $attack += $unit->amount * $unit->gameUnit->amount;
            }
        }

        return $attack;
    }
}
