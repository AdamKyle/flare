<?php

namespace App\Game\Kingdoms\Handlers;


use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Game\Kingdoms\Values\UnitNames;

class KingdomAirshipHandler {

    /**
     * @var array $newBuildings
     */
    private array $newBuildings      = [];

    /**
     * @var array $attackingUnits
     */
    private array $attackingUnits    = [];

    /**
     * @var array $newAttackingUnits
     */
    private array $newAttackingUnits = [];

    /**
     * @var array $newUnits
     */
    private array $newUnits          = [];

    /**
     * Set attacking units.
     *
     * @param array $attackingUnits
     * @return $this
     */
    public function setAttackingUnits(array $attackingUnits): KingdomSiegeHandler {
        $this->attackingUnits = $attackingUnits;

        return $this;
    }

    /**
     * Get new defending buildings
     *
     * @return array
     */
    public function getNewBuildings(): array {
        return $this->newBuildings;
    }

    /**
     * Get new defending units.
     *
     * @return array
     */
    public function getNewUnits(): array {
        return $this->newUnits;
    }

    /**
     * Get new attacking units.
     *
     * @return array
     */
    public function getNewAttackingUnits(): array {
        return $this->newAttackingUnits;
    }

    /**
     * Handle airships.
     *
     * - Defenders airships are attacked first, then buildings.
     *
     * @param Kingdom $attackingKingdom
     * @param Kingdom $kingdom
     * @param float $damageReduction
     * @return Kingdom
     */
    public function handleAirships(Kingdom $attackingKingdom, Kingdom $kingdom, float $damageReduction): Kingdom {
        $kingdom = $this->handleUnits($attackingKingdom, $kingdom, UnitNames::AIRSHIP, $damageReduction);

        foreach ($kingdom->buildings as $building) {
            $airships = $this->getAirships(UnitNames::AIRSHIP);

            if (empty($trebuchets)) {
                return $kingdom;
            }

            $damage = $this->getAirShipAttack($attackingKingdom, $airships, $damageReduction);

            if ($damage <= 0) {
                $this->setNewAirshipUnits($airships);

                return $kingdom;
            }

            if ($building->current_durability <= 0 ) {
                $this->setNewAirshipUnits($trebuchets);

                continue;
            }

            if ($building->is_locked) {
                $this->setNewAirshipUnits($trebuchets);

                continue;
            }

            $this->damageBuildings($building, $trebuchets, $damage);
        }

        return $kingdom->refresh();
    }

    /**
     * Damage defender buildings.
     *
     * @param KingdomBuilding $building
     * @param array $siegeWeapons
     * @param int $damage
     * @return void
     */
    protected function damageBuildings(KingdomBuilding $building, array $siegeWeapons, int $damage) {

        if ($damage > $building->current_defence) {
            $damagePercentToBuilding = $building->current_defence / $damage;
        } else {
            $damagePercentToBuilding = $damage / $building->current_defence;;
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
            'name'       => $building->name,
            'durability' => $building->current_durability,
        ];
    }

    /**
     * Handle unit damage.
     *
     * @param Kingdom $attackingKingdom
     * @param Kingdom $kingdom
     * @param string $siegeWeaponName
     * @param float $damageReduction
     * @return Kingdom
     */
    protected function handleUnits(Kingdom $attackingKingdom, Kingdom $kingdom, string $siegeWeaponName, float $damageReduction): Kingdom {

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

            $unitDefence = $unit->amount * $unit->defence;
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
                'amount'  => $unit->amount,
                'name'    => $unit->gameUnit->name,
            ];
        }

        return $kingdom->refresh();
    }

    /**
     * Get the total attack for siege weapons.
     *
     * - Also deals with kingdom defence damage reduction.
     *
     * @param Kingdom $attackingKingdom
     * @param array $siegeWeaponDetails
     * @param float $damageReduction
     * @return int
     */
    protected function getAirShipAttack(Kingdom $attackingKingdom, array $siegeWeaponDetails, float $damageReduction): int {

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
     *
     * @param string $name
     * @return array
     */
    protected function getAirships(string $name): array {

        if (!empty($this->newAttackingUnits)) {
            $index = array_search($name, array_column($this->newAttackingUnits, 'name'));

            if ($index !== false) {
                return $this->newAttackingUnits[$index];
            }
        }

        $index    = array_search($name, array_column($this->attackingUnits, 'name'));
        $unitData = [];

        if ($index !== false) {
            $unitData = $this->attackingUnits[$index];
        }

        return $unitData;
    }

    /**
     * Updates the siege weapons with the new amount.
     *
     * @param array $siegeWeapon
     * @param int $damage
     * @param int $buildingDefence
     * @return void
     */
    protected function updateSiegeWeapons(array $siegeWeapon, int $damage, int $buildingDefence) {

        $damageToUnits = $buildingDefence / $damage;;

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
     * @param array $siegeWeapon
     * @return void
     */
    protected function setNewAirshipUnits(array $siegeWeapon) {
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
