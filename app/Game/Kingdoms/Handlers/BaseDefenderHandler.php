<?php

namespace App\Game\Kingdoms\Handlers;

use App\Game\Kingdoms\Values\UnitNames;

class BaseDefenderHandler {

    /**
     * @var array $attackingUnits
     */
    protected $attackingUnits = [];

    /**
     * Set attacking units.
     *
     * @param array $attackingUnits
     * @return $this
     */
    public function setAttackingUnits(array $attackingUnits) {
        $this->attackingUnits = $attackingUnits;

        return $this;
    }

    /**
     * Get attacking units.
     *
     * @return array
     */
    public function getAttackingUnits(): array {
        return $this->attackingUnits;
    }

    /**
     * Update the attacking units based on the damage.
     *
     * The damage is a % of amount / the damage to do.
     *
     * We can potentially ignore the siege weapons.
     *
     * @param float $attack
     * @param bool $ignoreSiegeWeapons
     * @return void
     */
    protected function updateAttackingUnits(float $attack, bool $ignoreSiegeWeapons = false): void {
        foreach ($this->attackingUnits as $index => $unitData) {

            if ($ignoreSiegeWeapons) {
                if ($unitData['name'] === UnitNames::RAM ||
                    $unitData['name'] === UnitNames::TREBUCHET ||
                    $unitData['name'] === UnitNames::CANNON
                ) {
                    continue;
                }
            }

            $newAmount = $unitData['amount'] - ($unitData['amount'] * $attack);

            if ($newAmount <= 0) {
                $newAmount = 0;
            }

            $unitData['amount'] = $newAmount;

            $this->attackingUnits[$index] = $unitData;
        }
    }

    /**
     * Get the total amount of attacking units.
     *
     * @return int
     */
    protected function getTotalAmountOfUnits(): int {
        $amount = 0;

        foreach ($this->attackingUnits as $unitData) {
            $amount += $unitData['amount'];
        }

        return $amount;
    }
}
