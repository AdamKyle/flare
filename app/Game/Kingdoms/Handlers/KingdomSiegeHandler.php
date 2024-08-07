<?php

namespace App\Game\Kingdoms\Handlers;

use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Game\Kingdoms\Values\UnitNames;

class KingdomSiegeHandler
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
    public function setAttackingUnits(array $attackingUnits): KingdomSiegeHandler
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
     * Handle ram attacks on walls and farms.
     */
    public function handleRams(Kingdom $attackingKingdom, Kingdom $kingdom, float $damageReduction): Kingdom
    {
        $buildingsForRam = $this->getBuildingsForRam($kingdom);

        foreach ($buildingsForRam as $building) {
            $rams = $this->getSiegeWeapons(UnitNames::RAM);

            if (empty($rams)) {
                return $kingdom;
            }

            $damage = $this->getSiegeWeaponAttack($attackingKingdom, $rams, $damageReduction);

            if ($damage <= 0) {
                $this->setNewSiegeUnits($rams);

                return $kingdom;
            }

            if ($building->current_durability <= 0) {
                $this->setNewSiegeUnits($rams);

                continue;
            }

            if ($building->is_locked) {
                $this->setNewSiegeUnits($rams);

                continue;
            }

            $this->damageBuildings($building, $rams, $damage);
        }

        return $kingdom->refresh();
    }

    /**
     * Handle Trebuchet attacks.
     *
     * - Also attacks the units.
     */
    public function handleTrebuchets(Kingdom $attackingKingdom, Kingdom $kingdom, float $damageReduction): Kingdom
    {
        foreach ($kingdom->buildings as $building) {
            $trebuchets = $this->getSiegeWeapons(UnitNames::TREBUCHET);

            if (empty($trebuchets)) {
                return $kingdom;
            }

            $damage = $this->getSiegeWeaponAttack($attackingKingdom, $trebuchets, $damageReduction);

            if ($damage <= 0) {
                $this->setNewSiegeUnits($trebuchets);

                return $kingdom;
            }

            if ($building->current_durability <= 0) {
                $this->setNewSiegeUnits($trebuchets);

                continue;
            }

            if ($building->is_locked) {
                $this->setNewSiegeUnits($trebuchets);

                continue;
            }

            $this->damageBuildings($building, $trebuchets, $damage);
        }

        return $this->handleUnits($attackingKingdom, $kingdom, UnitNames::TREBUCHET, $damageReduction);
    }

    /**
     * Handle cannons
     *
     * - Also damages units.
     */
    public function handleCannons(Kingdom $attackingKingdom, Kingdom $kingdom, float $damageReduction): Kingdom
    {
        foreach ($kingdom->buildings as $building) {
            $cannons = $this->getSiegeWeapons(UnitNames::CANNON);

            if (empty($cannons)) {
                return $kingdom;
            }

            $damage = $this->getSiegeWeaponAttack($attackingKingdom, $cannons, $damageReduction);

            if ($damage <= 0) {
                $this->setNewSiegeUnits($cannons);

                return $kingdom;
            }

            if ($building->current_durability <= 0) {
                $this->setNewSiegeUnits($cannons);

                continue;
            }

            if ($building->is_locked) {
                $this->setNewSiegeUnits($cannons);

                continue;
            }

            $this->damageBuildings($building, $cannons, $damage);
        }

        return $this->handleUnits($attackingKingdom, $kingdom, UnitNames::CANNON, $damageReduction);
    }

    /**
     * Damage defender buildings.
     *
     * @return void
     */
    protected function damageBuildings(KingdomBuilding $building, array $siegeWeapons, int $damage)
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

        $this->updateSiegeWeapons($siegeWeapons, $damage, $building->current_defence);
    }

    /**
     * Handle unit damage.
     */
    protected function handleUnits(Kingdom $attackingKingdom, Kingdom $kingdom, string $siegeWeaponName, float $damageReduction): Kingdom
    {

        foreach ($kingdom->units as $unit) {
            $siegeWeapons = $this->getSiegeWeapons($siegeWeaponName);

            if (empty($siegeWeapons)) {
                return $kingdom;
            }

            $damage = $this->getSiegeWeaponAttack($attackingKingdom, $siegeWeapons, $damageReduction);

            if ($damage <= 0) {
                $this->setNewSiegeUnits($siegeWeapons);

                return $kingdom;
            }

            if ($unit->amount <= 0) {
                $this->setNewSiegeUnits($siegeWeapons);

                continue;
            }

            $unitDefence = $unit->amount * $unit->gameUnit->defence;

            if ($unitDefence <= 0) {
                $this->setNewSiegeUnits($siegeWeapons);

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
    protected function getSiegeWeaponAttack(Kingdom $attackingKingdom, array $siegeWeaponDetails, float $damageReduction): int
    {

        foreach ($attackingKingdom->units as $unit) {

            if ($unit->gameUnit->name === $siegeWeaponDetails['name']) {
                $attack = $siegeWeaponDetails['amount'] * $unit->gameUnit->attack;

                return floor($attack - ($attack * $damageReduction));
            }
        }

        return 0;
    }

    /**
     * Get the siege weapon info based on the name.
     */
    protected function getSiegeWeapons(string $name): array
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

        $this->setNewSiegeUnits($siegeWeapon);
    }

    /**
     * Set the siege weapon to the new attacking units.
     *
     * @return void
     */
    protected function setNewSiegeUnits(array $siegeWeapon)
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

    /**
     * Get the buildings for the ram - walls and farms.
     */
    protected function getBuildingsForRam(Kingdom $kingdom): array
    {
        $buildings = [];

        foreach ($kingdom->buildings as $building) {
            if ($building->is_walls || $building->is_farm) {
                $buildings[] = $building;
            }
        }

        return $buildings;
    }
}
