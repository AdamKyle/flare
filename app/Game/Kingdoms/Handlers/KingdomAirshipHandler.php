<?php

namespace App\Game\Kingdoms\Handlers;

use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Game\Kingdoms\Values\UnitNames;

class KingdomAirshipHandler
{
    private array $newBuildings = [];

    private array $attackingUnits = [];

    private array $newAttackingUnits = [];

    private array $newUnits = [];

    /**
     * Set attacking units.
     *
     * @return $this
     */
    public function setAttackingUnits(array $attackingUnits): KingdomAirshipHandler
    {
        $this->attackingUnits = $attackingUnits;

        return $this;
    }

    /**
     * Get new defending buildings
     */
    public function getNewBuildings(): array
    {
        return $this->newBuildings;
    }

    /**
     * Get new defending units.
     */
    public function getNewUnits(): array
    {
        return $this->newUnits;
    }

    /**
     * Get new attacking units.
     */
    public function getNewAttackingUnits(): array
    {
        return $this->newAttackingUnits;
    }

    /**
     * Handle airships.
     *
     * - Defenders airships are attacked first, then buildings.
     */
    public function handleAirships(Kingdom $attackingKingdom, Kingdom $kingdom, float $damageReduction): Kingdom
    {

        $kingdom = $this->handleUnits($attackingKingdom, $kingdom, UnitNames::AIRSHIP, $damageReduction);

        foreach ($kingdom->buildings as $building) {
            $airships = $this->getAirships(UnitNames::AIRSHIP);

            if (empty($airships)) {
                return $kingdom;
            }

            $damage = $this->getAirShipAttack($attackingKingdom, $airships, $damageReduction);

            if ($damage <= 0) {
                $this->setNewAirshipUnits($airships);

                return $kingdom;
            }

            if ($building->current_durability <= 0) {
                $this->setNewAirshipUnits($airships);

                continue;
            }

            if ($building->is_locked) {
                $this->setNewAirshipUnits($airships);

                continue;
            }

            $this->damageBuildings($building, $damage);
        }

        return $kingdom->refresh();
    }

    /**
     * Damage defender buildings.
     */
    protected function damageBuildings(KingdomBuilding $building, int $damage): void
    {

        if ($damage > $building->current_defence) {
            $damagePercentToBuilding = $building->current_defence / $damage;
        } else {
            $damagePercentToBuilding = $damage / $building->current_defence;
        }

        $newDurability = $building->current_durability;

        if ($damagePercentToBuilding > 1) {
            $damagePercentToBuilding = 1;
        }

        $newDurability = $newDurability - ($newDurability * $damagePercentToBuilding);

        $building->update([
            'current_durability' => $newDurability <= 0 ? 0 : $newDurability,
        ]);

        $building = $building->refresh();

        $this->newBuildings[] = [
            'name' => $building->name,
            'durability' => $building->current_durability,
        ];
    }

    /**
     * Handle unit damage.
     */
    protected function handleUnits(Kingdom $attackingKingdom, Kingdom $kingdom, string $siegeWeaponName, float $damageReduction): Kingdom
    {

        foreach ($kingdom->units as $unit) {
            $siegeWeapons = $this->getAirships($siegeWeaponName);

            if (empty($siegeWeapons)) {
                return $kingdom;
            }

            $damage = $this->getAirShipAttack($attackingKingdom, $siegeWeapons, $damageReduction);

            if ($damage <= 0) {
                $this->setNewAirshipUnits($siegeWeapons);

                return $kingdom;
            }

            if ($unit->amount <= 0) {
                $this->setNewAirshipUnits($siegeWeapons);

                continue;
            }

            $unitDefence = $unit->amount * $unit->gameUnit->defence;
            $unitDefence = $unitDefence + ($unitDefence * $kingdom->fetchAirShipDefenceIncrease());

            if ($unitDefence <= 0) {
                $this->setNewAirshipUnits($siegeWeapons);

                continue;
            }

            $damagePercentToUnit = $damage / $unitDefence;

            if ($damagePercentToUnit > 1) {
                $damagePercentToUnit = 1;
            }

            $newAmount = $unit->amount - ($unit->amount * $damagePercentToUnit);

            $unit->update([
                'amount' => $newAmount <= 0 ? 0 : $newAmount,
            ]);

            $unit = $unit->refresh();

            $this->newUnits[] = [
                'unit_id' => $unit->id,
                'amount' => $unit->amount,
                'name' => $unit->gameUnit->name,
            ];
        }

        return $kingdom->refresh();
    }

    /**
     * Get the total attack for siege weapons.
     *
     * - Also deals with kingdom defence damage reduction.
     */
    protected function getAirShipAttack(Kingdom $attackingKingdom, array $siegeWeaponDetails, float $damageReduction): int
    {

        foreach ($attackingKingdom->units as $unit) {

            if ($unit->gameUnit->name === $siegeWeaponDetails['name']) {
                $attack = $siegeWeaponDetails['amount'] * $unit->gameUnit->attack;

                $attack = $attack + $attack * $attackingKingdom->fetchAirShipAttackIncrease();

                return floor($attack - ($attack * $damageReduction));
            }
        }

        return 0;
    }

    /**
     * Get the airship unit info based on the name.
     */
    protected function getAirships(string $name): array
    {

        if (! empty($this->newAttackingUnits)) {
            $index = array_search($name, array_column($this->newAttackingUnits, 'name'));

            if ($index !== false) {
                return $this->newAttackingUnits[$index];
            }
        }

        $index = array_search($name, array_column($this->attackingUnits, 'name'));
        $unitData = [];

        if ($index !== false) {
            $unitData = $this->attackingUnits[$index];
        }

        return $unitData;
    }

    /**
     * Updates the siege weapons with the new amount.
     *
     * @return void
     */
    protected function updateSiegeWeapons(array $siegeWeapon, int $damage, int $buildingDefence)
    {

        $damageToUnits = $buildingDefence / $damage;

        if ($damageToUnits > 1) {
            $damageToUnits = 1;
        }

        $newAmount = $siegeWeapon['amount'] - ($siegeWeapon['amount'] * $damageToUnits);

        $siegeWeapon['amount'] = $newAmount;

        $this->setNewAirshipUnits($siegeWeapon);
    }

    /**
     * Set the siege weapon to the new attacking units.
     *
     * @return void
     */
    protected function setNewAirshipUnits(array $siegeWeapon)
    {
        if (empty($this->newAttackingUnits)) {
            $this->newAttackingUnits[] = $siegeWeapon;
        } else {
            $index = array_search($siegeWeapon['name'], array_column($this->newAttackingUnits, 'name'));

            if ($index !== false) {
                $this->newAttackingUnits[$index] = $siegeWeapon;
            } else {
                $this->newAttackingUnits[] = $siegeWeapon;
            }
        }
    }
}
