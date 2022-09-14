<?php

namespace App\Game\Kingdoms\Handlers;


use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomLog;
use App\Flare\Models\KingdomUnit;
use App\Flare\Models\UnitMovementQueue;
use App\Flare\Values\KingdomLogStatusValue;
use App\Game\Kingdoms\Jobs\MoveUnits;
use App\Game\Kingdoms\Service\UnitMovementService;
use App\Game\Kingdoms\Service\UpdateKingdom;
use App\Game\Kingdoms\Traits\CalculateMorale;
use App\Game\Maps\Calculations\DistanceCalculation;
use App\Game\Messages\Events\ServerMessageEvent;

class AttackKingdomWithUnitsHandler {

    use CalculateMorale;

    private DistanceCalculation $distanceCalculation;

    private UnitMovementService $unitMovementService;

    private UpdateKingdom $updateKingdom;

    private array $oldAttackingUnits    = [];

    private array $newAttackingUnits    = [];

    private array $oldDefenderUnits     = [];

    private array $newDefenderUnits     = [];

    private array $oldDefenderBuildings = [];

    private array $newDefenderBuildings = [];

    private float $currentMorale;

    public function __construct(DistanceCalculation $distanceCalculation,
                                UnitMovementService $unitMovementService,
                                UpdateKingdom $updateKingdom
    ) {
        $this->distanceCalculation = $distanceCalculation;
        $this->unitMovementService = $unitMovementService;
        $this->updateKingdom       = $updateKingdom;
    }

    public function attackKingdomWithUnits(Kingdom $kingdom, Kingdom $attackingKingdom, array $unitsAttacking): void {

        $this->currentMorale = $kingdom->current_morale;

        $this->setOldKingdomBuildings($kingdom);
        $this->setOldKingdomUnits($kingdom);
        $this->setOldAttackingUnits($attackingKingdom, $unitsAttacking);

        $this->siegeAttack($attackingKingdom, $kingdom, $unitsAttacking);
        $this->unitsAttack($attackingKingdom, $kingdom, $unitsAttacking);

        $this->returnSurvivingUnits($attackingKingdom, $kingdom);

        $this->createLogForAttacker($attackingKingdom, $kingdom);
        $this->createLogForDefender($attackingKingdom, $kingdom);
    }

    protected function unitsAttack(Kingdom $attackingKingdom, Kingdom $kingdom, array $unitsAttacking): void {
        $damageReduction = $this->getTotalDamageReduction($kingdom);
        $defence         = $this->getDefendingKingdomUnitDefence($kingdom);
        $unitAttack      = 0;

        foreach ($unitsAttacking as $unitData) {
            $unit = KingdomUnit::where('kingdom_id', $attackingKingdom->id)->where('id', $unitData['unit_id'])->first();

            if (!$unit->siege_weapon && !$unit->is_settler) {
                $unitAttack += $unit->amount * $unit->gameUnit->attack;
            }
        }

        $unitAttack = ceil($unitAttack - ($unitAttack * $damageReduction));

        $damageToAttacker = $defence / $unitAttack;

        if ($damageToAttacker > 1) {
            $damageToAttacker = 1;
        }

        foreach ($unitsAttacking as $unitData) {
            $unit = KingdomUnit::where('kingdom_id', $attackingKingdom->id)->where('id', $unitData['unit_id'])->first();

            if (!$unit->gameUnit->siege_weapon && !$unit->gameUnit->is_settler) {

                $newAmount = $unitData['amount'] - ($unitData['amount'] * $damageToAttacker);

                if (!$this->updateAttackingUnit($unit->id, floor($newAmount))) {
                    $this->newAttackingUnits[] = [
                        'unit_id' => $unit->id,
                        'name'    => $unit->gameUnit->name,
                        'amount'  => $newAmount
                    ];
                }
            }
        }

        $healingAmount = $this->getHealingAmount($this->newAttackingUnits);

        dump('Healing Amount: ' . $healingAmount);

        if ($healingAmount > 0.0) {
            dump('here? - ready to heal');
            $newUnits = $this->healUnits($this->newAttackingUnits, $this->oldAttackingUnits, $healingAmount);

            $this->newAttackingUnits = $newUnits;
        }

        $damageToDefenderUnit = $unitAttack / $defence;

        if ($damageToDefenderUnit > 1.0) {
            $damageToDefenderUnit = 1.0;
        }

        foreach ($kingdom->units as $unit) {
            if (!$unit->gameUnit->siege_weapon) {
                $newAmount = $unit->amount - ($unit->amount * $damageToDefenderUnit);

                $unit->update([
                    'amount' => $newAmount
                ]);

                $unit = $unit->refresh();

                if (!$this->updateDefendingUnitsUnit($unit->id, floor($newAmount))) {
                    $this->newDefenderUnits[] = [
                        'unit_id' => $unit->id,
                        'name'    => $unit->gameUnit->name,
                        'amount'  => $newAmount
                    ];
                }
            }
        }

        $healingAmount = $this->getHealingAmount($this->newDefenderUnits);

        if ($healingAmount > 0.0) {
            $newUnits = $this->healUnits($this->newDefenderUnits, $this->oldDefenderUnits, $healingAmount);

            $this->newDefenderUnits = $newUnits;
        }
    }

    /**
     * Update Attacking Unit Info.
     *
     * Can return false if the unit does not exist.
     *
     * @param int $unitId
     * @param int $amount
     * @return bool
     */
    protected function updateAttackingUnit(int $unitId, int $amount): bool {
        foreach ($this->newAttackingUnits as $index => $unitInfo) {
            if ($unitInfo['unit_id'] === $unitId) {
                $this->newAttackingUnits[$index]['amount'] = $amount;

                return true;
            }
        }

        return false;
    }

    /**
     * Update Defending Unit Info.
     *
     * Can return false if the unit does not exist.
     *
     * @param int $unitId
     * @param int $amount
     * @return bool
     */
    protected function updateDefendingUnitsUnit(int $unitId, int $amount): bool {
        foreach ($this->newDefenderUnits as $index => $unitInfo) {
            if ($unitInfo['unit_id'] === $unitId) {
                $this->newDefenderUnits[$index]['amount'] = $amount;

                return true;
            }
        }

        return false;
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
        } else {
            $this->newDefenderBuildings = $this->oldDefenderBuildings;
            $this->newDefenderUnits     = $this->oldDefenderUnits;
            $this->newAttackingUnits    = $this->oldAttackingUnits;
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
                'unit_id'    => $building->id,
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

            $building = $building->refresh();

            $this->newDefenderBuildings[] = [
                'unit_id'    => $building->id,
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

                $unit = $unit->refresh();

                $this->newDefenderUnits[] = [
                    'unit_id' => $unit->id,
                    'name'    => $unit->gameUnit->name,
                    'amount'  => $newAmount,
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
            $unit = KingdomUnit::where('kingdom_id', $attackingKingdom->id)->where('id', $unitData['unit_id'])->first();

            if ($unit->gameUnit->siege_weapon) {
                $newAmount = $unitData['amount'] - ($unitData['amount'] * $damageToSiegeWeapons);

                $this->newAttackingUnits[] = [
                    'unit_id' => $unit->id,
                    'name'    => $unit->gameUnit->name,
                    'amount'  => $newAmount,
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
                'unit_id' => $unit->id,
                'name'    => $unit->gameUnit->name,
                'amount'  => $unit->amount,
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
            $unit = KingdomUnit::where('kingdom_id', $attackingKingdom->id)->where('id', $unitData['unit_id'])->first();

            $this->oldAttackingUnits[] = [
                'unit_id' => $unit->id,
                'name'    => $unit->gameUnit->name,
                'amount'  => $unitData['amount']
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

        $newDamageReduction = 0.0;


        if ($totalDefence < 1) {
            return 0.0;
        }

        if ($totalDefence > 1) {
            $newDamageReduction = ($totalDefence - 1) / 5;
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

            if ($building->current_durability <= 0) {
                continue;
            }

            if ($building->current_durability < $building->max_durability)  {
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
            $unit = KingdomUnit::where('kingdom_id', $attackingKingdom->id)->where('id', $unitData['unit_id'])->first();

            if ($unit->gameUnit->siege_weapon) {
                $totalAttack += $unit->gameUnit->attack * $unitData['amount'];
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

            if ($unitData['amount'] > 0 && !$unit->siege_weapon && !$unit->is_settler) {
                $originalAmount = $this->getOriginalAmount($unitData['name'], $oldUnits);

                if ($originalAmount === 0) {
                    continue;
                }

                $newAmount = $unitData['amount'] + $unitData['amount'] * $healAmount;


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

    protected function createLogForAttacker(Kingdom $attackingKingdom, Kingdom $defenderKingdom): void {
        $logDetails = $this->createBaseAttributes($attackingKingdom, $defenderKingdom);

        $logDetails['character_id']           = $attackingKingdom->character_id;
        $logDetails['attacking_character_id'] = $attackingKingdom->character_id;

        KingdomLog::create($logDetails);

        event(new ServerMessageEvent($attackingKingdom->character->user, 'Your attack has landed on kingdom: ' .
            $defenderKingdom->name . ' on the plane: ' . $defenderKingdom->gameMap->name . ' At (X/Y): ' . $defenderKingdom->x_position . '/' . $defenderKingdom->y_position .
            ' you have a new attack log.'));

        $character = $attackingKingdom->character->refresh();

        $this->updateKingdom->updateKingdomAllKingdoms($character);
        $this->updateKingdom->updateKingdomLogs($character, true);

    }

    protected function createLogForDefender(Kingdom $attackingKingdom, Kingdom $defenderKingdom): void {
        if ($defenderKingdom->npc_owned) {
            return;
        }

        $logDetails = $this->createBaseAttributes($attackingKingdom, $defenderKingdom);

        $logDetails['character_id']           = $defenderKingdom->character_id;
        $logDetails['attacking_character_id'] = $attackingKingdom->character_id;

        KingdomLog::create($logDetails);

        event(new ServerMessageEvent($defenderKingdom->character->user, $attackingKingdom->character->name . ' has attacked your kingdom: ' .
            $defenderKingdom->name . ' on the plane: ' . $defenderKingdom->gameMap->name . ' At (X/Y): ' . $defenderKingdom->x_position . '/' . $defenderKingdom->y_position .
            ' you have a new attack log.'));

        $character = $defenderKingdom->character->refresh();

        $this->updateKingdom->updateKingdomAllKingdoms($character);
        $this->updateKingdom->updateKingdomLogs($character, true);
    }

    protected function createBaseAttributes(Kingdom $attackingKingdom, Kingdom $defenderKingdom): array {

        $newMorale = $this->currentMorale - $defenderKingdom->current_morale;

        return [
            'to_kingdom_id'   => $defenderKingdom->id,
            'from_kingdom_id' => $attackingKingdom->id,
            'status'          => KingdomLogStatusValue::ATTACKED,
            'old_buildings'   => $this->oldDefenderBuildings,
            'new_buildings'   => $this->newDefenderBuildings,
            'old_units'       => $this->oldDefenderUnits,
            'new_units'       => $this->newDefenderUnits,
            'units_sent'      => $this->oldAttackingUnits,
            'units_survived'  => $this->newAttackingUnits,
            'morale_loss'     => max($newMorale, 0),
            'published'       => true,
        ];
    }

    protected function returnSurvivingUnits(Kingdom $attackingKingdom, Kingdom $defendingKingdom): void {

        if (!$this->isThereAnySurvivingUnits()) {
            return;
        }

        $character     = $attackingKingdom->character;

        $time          = $this->unitMovementService->getDistanceTime($character, $attackingKingdom, $defendingKingdom);

        $minutes       = now()->addMinutes($time);

        $unitMovementQueue = UnitMovementQueue::create([
            'character_id'      => $character->id,
            'from_kingdom_id'   => $defendingKingdom->id,
            'to_kingdom_id'     => $attackingKingdom->id,
            'units_moving'      => $this->newAttackingUnits,
            'completed_at'      => $minutes,
            'started_at'        => now(),
            'moving_to_x'       => $attackingKingdom->x_position,
            'moving_to_y'       => $attackingKingdom->y_position,
            'from_x'            => $defendingKingdom->x_position,
            'from_y'            => $defendingKingdom->y_position,
            'is_attacking'      => false,
            'is_recalled'       => false,
            'is_returning'      => true,
            'is_moving'         => false,
        ]);

        MoveUnits::dispatch($unitMovementQueue->id)->delay($minutes);
    }

    protected function isThereAnySurvivingUnits(): bool {
        $attackingUnitAmount = 0;

        foreach ($this->newAttackingUnits as $attackingUnit) {
            if ($attackingUnit['amount'] > 0) {
                $attackingUnitAmount = $attackingUnit['amount'];
            }
        }

        return $attackingUnitAmount > 0;
    }
}
