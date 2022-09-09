<?php

namespace App\Game\Kingdoms\Handlers;


use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomUnit;
use App\Game\Kingdoms\Traits\CalculateMorale;

class AttackKingdomWithUnitsHandler {

    use CalculateMorale;

    private array $oldAttackingUnits    = [];

    private array $newAttackingUnits    = [];

    private array $oldDefenderUnits     = [];

    private array $newDefenderUnits     = [];

    private array $oldDefenderBuildings = [];

    private array $newDefenderBuildings = [];

    private float $currentMorale;

    public function attackKingdomWithUnits(Kingdom $kingdom, Kingdom $attackingKingdom, array $unitsAttacking): void {

        $this->setOldKingdomBuildings($kingdom);
        $this->setOldKingdomUnits($kingdom);
        $this->setOldAttackingUnits($attackingKingdom, $unitsAttacking);

        $this->siegeAttack($attackingKingdom, $kingdom, $unitsAttacking);
        $this->unitsAttack($attackingKingdom, $kingdom, $unitsAttacking);

        $kingdom = $kingdom->refresh();
    }

    protected function unitsAttack(Kingdom $attackingKingdom, Kingdom $kingdom, array $unitsAttacking): void {
        $damageReduction = $this->getTotalDamageReduction($kingdom);
        $defence         = $this->getDefendingKingdomUnitDefence($kingdom);
        $unitAttack      = 0;

        foreach ($unitsAttacking as $unitData) {
            $unit = KingdomUnit::where('kingdom_id', $attackingKingdom->id)->where('unit_id', $unitData['unit_id'])->first();

            if (!$unit->siege_weapon && !$unit->is_settler) {
                $unitAttack += $unit->amount * $unit->game_unit->attack;
            }
        }

        $unitAttack = ceil($unitAttack - ($unitAttack * $damageReduction));

        $damageToAttacker = $defence / $unitAttack;

        if ($damageToAttacker > 1) {
            $damageToAttacker = 1;
        }

        foreach ($unitsAttacking as $unitData) {
            $unit = KingdomUnit::where('kingdom_id', $attackingKingdom->id)->where('unit_id', $unitData['unit_id'])->first();

            if (!$unit->gameUnit->siege_weapon && !$unit->gameUnit->is_settler) {

                $newAmount = ceil($unitData['amount'] - ($unitData['amount'] * $damageToAttacker));

                $this->newAttackingUnits[] = [
                    'name'   => $unit->gameUnit->name,
                    'amount' => $newAmount
                ];
            }
        }

        $healingAmount = $this->getHealingAmount($this->newAttackingUnits);

        if ($healingAmount > 0.0) {
            $newUnits = $this->healUnits($this->newAttackingUnits, $this->oldAttackingUnits, $healingAmount);

            $this->newAttackingUnits = $newUnits;
        }

        $damageToDefenderUnit = $unitAttack / $defence;

        if ($damageToAttacker > 1) {
            $damageToDefenderUnit = 1;
        }

        foreach ($kingdom->units as $unit) {
            if (!$unit->gameUnit->siege_weapon) {
                $newAmount = ceil($unit->amount - ($unit->amount * $damageToDefenderUnit));

                $unit->update([
                    'amount' => $newAmount
                ]);

                $this->newDefenderUnits[] = [
                    'name'   => $unit->gameUnit->name,
                    'amount' => $newAmount
                ];
            }
        }

        $healingAmount = $this->getHealingAmount($this->newDefenderUnits);

        if ($healingAmount > 0.0) {
            $newUnits = $this->healUnits($this->newDefenderUnits, $this->oldDefenderUnits, $healingAmount);

            $this->newDefenderUnits = $newUnits;
        }
    }

    /**
     * Siege Attack.
     *
     * @param Kingdom $attackingKingdom
     * @param Kingdom $kingdom
     * @param array $unitsAttacking
     * @return void
     */
    protected function siegeAttack(Kingdom $attackingKingdom, Kingdom $kingdom, array $unitsAttacking): void {
        $damageReduction = $this->getTotalDamageReduction($kingdom);
        $defence         = $this->getBuildingsDefence($kingdom);
        $siegeAttack     = $this->getSiegeUnitAttack($attackingKingdom, $unitsAttacking);

        if ($siegeAttack > 0) {
            $siegeAttack = $siegeAttack - ($siegeAttack * $damageReduction);

            $this->setNewKingdomBuildingsAndSiegeWeapons($attackingKingdom, $kingdom, $unitsAttacking, $siegeAttack, $defence);
        }
    }

    /**
     * Set the old building data.
     *
     * @param Kingdom $kingdom
     * @return void
     */
    protected function setOldKingdomBuildings(Kingdom $kingdom) {
        foreach ($kingdom->buildings as $building) {
            $this->oldDefenderBuildings[] = [
                'name'       => $building->name,
                'durability' => $building->current_durability,
            ];
        }
    }

    /**
     * Attack the buildings with siege weapons.
     *
     * @param Kingdom $attackingKingdom
     * @param Kingdom $kingdom
     * @param array $attackingUnits
     * @param float $damage
     * @param float $defence
     * @return void
     */
    protected function setNewKingdomBuildingsAndSiegeWeapons(Kingdom $attackingKingdom, Kingdom $kingdom, array $attackingUnits, float $damage, float $defence) {
        foreach ($kingdom->buildings as $building) {
            $damage = $damage / $defence;

            if ($damage > 1) {
                $damage = 1;
            }

            $currentDurability = $building->current_durability - ($building->current_durability * $damage);

            $building->update([
                'current_durability' => $currentDurability < 0 ? 0 : floor($currentDurability),
            ]);

            $building->refresh();

            $this->newDefenderBuildings[] = [
                'name'       => $building->name,
                'durability' => $building->current_durability,
            ];
        }

        foreach ($kingdom->units as $unit) {
            if ($unit->gameUnit->siege_weapon) {
                $damage = $damage / $defence;

                if ($damage > 1) {
                    $damage = 1;
                }

                $newAmount = $unit->amount - ($unit->amount * $damage);

                $unit->update([
                    'amount' => $newAmount
                ]);

                $this->newDefenderUnits[] = [
                    'name'   => $unit->gameUnit->name,
                    'amount' => $newAmount,
                ];
            }
        }

        $this->currentMorale = $kingdom->current_morale;

        $newMorale = $this->calculateNewMorale($kingdom, $kingdom->current_morale);

        $kingdom->update([
            'current_morale' => $newMorale
        ]);

        $damageToSiegeWeapons = $damage / $defence;

        if ($damageToSiegeWeapons > 1) {
            $damageToSiegeWeapons = 1;
        }

        foreach ($attackingUnits as $unitData) {
            $unit = KingdomUnit::where('kingdom_id', $attackingKingdom->id)->where('unit_id', $unitData['unit_id'])->first();

            if ($unit->gameUnit->siege_weapon) {
                $newAmount = $unit->amount - ($unit->amount * $damageToSiegeWeapons);

                $this->newAttackingUnits[] = [
                    'name'   => $unit->gameUnit->name,
                    'amount' => $newAmount,
                ];
            }
        }
    }

    /**
     * Set the old unit data.
     *
     * @param Kingdom $kingdom
     * @return void
     */
    protected function setOldKingdomUnits(Kingdom $kingdom): void {
        foreach ($kingdom->units as $unit) {
            $this->oldDefenderUnits[] = [
                'name'   => $unit->gameUnit->name,
                'amount' => $unit->amount,
            ];
        }
    }

    /**
     * Set the old attacking unit data.
     *
     * @param Kingdom $attackingKingdom
     * @param array $units
     * @return void
     */
    protected function setOldAttackingUnits(Kingdom $attackingKingdom, array $units): void {
        foreach ($units as $unitData) {
            $unit = KingdomUnit::where('kingdom_id', $attackingKingdom->id)->where('unit_id', $unitData['unit_id'])->first();

            $this->oldAttackingUnits[] = [
                'name'   => $unit->gameUnit->name,
                'amount' => $unitData['amount']
            ];
        }
    }

    /**
     * Get the total damage reduction.
     *
     * @param Kingdom $kingdom
     * @return float
     */
    protected function getTotalDamageReduction(Kingdom $kingdom): float {
        $damageReduction = 0.05;

        $totalDefence = $kingdom->fetchKingdomDefenceBonus();

        if ($totalDefence < 1) {
            return 0.0;
        }

        if ($totalDefence > 1) {
            $newDamageReduction = ($totalDefence - 1) / 0.05;
        }

        if ($newDamageReduction < 0.05) {
            return $damageReduction;
        }

        return $newDamageReduction;
    }

    /**
     * Get the buildings and siege weapons total defence.
     *
     * @param Kingdom $kingdom
     * @return int
     */
    protected function getBuildingsDefence(Kingdom $kingdom): int {
        $defence = 0;

        foreach ($kingdom->buildings as $building) {
            $percentageOfDefence = 0.0;

            if ($building->current_durability < $building->max_durability) {
                $percentageOfDefence = $building->max_durability / $building->current_durability;
            }

            $defence += ceil($building->current_defence - ($building->current_defence * $percentageOfDefence));
        }

        foreach ($kingdom->units as $unit) {
            if ($unit->gameUnit->siege_weapon) {
                $defence += $unit->gameUnit->defence;
            }
        }

        return $defence;
    }

    /**
     * Get defending kingdom units defence.
     *
     * - Non siege weapons.
     *
     * @param Kingdom $kingdom
     * @return int
     */
    protected function getDefendingKingdomUnitDefence(Kingdom $kingdom): int {
        $defence = 0;

        foreach ($kingdom->units as $unit) {
            if (!$unit->gameUnit->siege_weapon) {
                $defence += $unit->gameUnit->defence;
            }
        }

        return $defence;
    }

    /**
     * Get Siege Unit Attack.
     *
     * @param Kingdom $attackingKingdom
     * @param array $units
     * @return int
     */
    protected function getSiegeUnitAttack(Kingdom $attackingKingdom, array $units): int {
        $totalAttack = 0;

        foreach ($units as $unitData) {
            $unit = KingdomUnit::where('kingdom_id', $attackingKingdom->id)->where('unit_id', $unitData['unit_id'])->first();

            if ($unit->gameUnit->siege_weapon) {
                $totalAttack += $unit->gameUnit->attack * $unitData['attack'];
            }
        }

        return $totalAttack;
    }

    protected function getHealingAmount(array $units): float {
        $healingPercentage = 0.0;

        foreach ($units as $unitData) {
            $unit = GameUnit::where('name', $unitData['name'])->first();

            if ($unitData['amount'] > 0 && $unit->can_heal) {
                $healingPercentage += $unitData['amount'] * $unit->heal_percentage;
            }
        }

        return $healingPercentage;
    }

    protected function healUnits(array $newUnits, array $oldUnits, float $healAmount): array {
        foreach ($newUnits as $index => $unitData) {
            $unit = GameUnit::where('name', $unitData['name'])->first();

            if ($unit['amount'] > 0 && !$unit->siege_weapon && $unit->is_settler) {
                $originalAmount = $this->getOriginalAmount($unitData['name'], $oldUnits);

                if ($originalAmount === 0) {
                    continue;
                }

                $newAmount = $unit['amount'] + $unit['amount'] * $healAmount;

                if ($newAmount > $originalAmount) {
                    $newAmount = $originalAmount;
                }

                $newUnits[$index]['amount'] = $newAmount;
            }
        }

        return $newUnits;
    }

    protected function getOriginalAmount(string $name, array $units): int {
        $amount = 0;

        foreach ($units as $unitData) {
            if ($unitData['name'] === $name) {
                $amount = $unitData['amount'];
            }
        }

        return $amount;
    }
}
