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

    /**
     * @var DistanceCalculation $distanceCalculation
     */
    private DistanceCalculation $distanceCalculation;

    /**
     * @var UnitMovementService $unitMovementService
     */
    private UnitMovementService $unitMovementService;

    /**
     * @var UpdateKingdom $updateKingdom
     */
    private UpdateKingdom $updateKingdom;

    /**
     * @var KingdomSiegeHandler $kingdomSiegeHandler
     */
    private KingdomSiegeHandler $kingdomSiegeHandler;

    /**
     * @var KingdomUnitHandler $kingdomUnitHandler
     */
    private KingdomUnitHandler $kingdomUnitHandler;

    /**
     * @var array $oldAttackingUnits
     */
    private array $oldAttackingUnits    = [];

    /**
     * @var array $newAttackingUnits
     */
    private array $newAttackingUnits    = [];

    /**
     * @var array $oldDefenderUnits
     */
    private array $oldDefenderUnits     = [];

    /**
     * @var array $newDefenderUnits
     */
    private array $newDefenderUnits     = [];

    /**
     * @var array $oldDefenderBuildings
     */
    private array $oldDefenderBuildings = [];

    /**
     * @var array $newDefenderBuildings
     */
    private array $newDefenderBuildings = [];

    /**
     * @var float $currentMorale
     */
    private float $currentMorale;

    /**
     * @param DistanceCalculation $distanceCalculation
     * @param UnitMovementService $unitMovementService
     * @param UpdateKingdom $updateKingdom
     * @param KingdomSiegeHandler $kingdomSiegeHandler
     * @param KingdomUnitHandler $kingdomUnitHandler
     */
    public function __construct(DistanceCalculation $distanceCalculation,
                                UnitMovementService $unitMovementService,
                                UpdateKingdom $updateKingdom,
                                KingdomSiegeHandler $kingdomSiegeHandler,
                                KingdomUnitHandler $kingdomUnitHandler,
    ) {
        $this->distanceCalculation = $distanceCalculation;
        $this->unitMovementService = $unitMovementService;
        $this->updateKingdom       = $updateKingdom;
        $this->kingdomSiegeHandler = $kingdomSiegeHandler;
        $this->kingdomUnitHandler  = $kingdomUnitHandler;
    }

    public function attackKingdomWithUnits(Kingdom $kingdom, Kingdom $attackingKingdom, array $unitsAttacking): void {
        $this->currentMorale = $kingdom->current_morale;

        $this->setOldKingdomBuildings($kingdom);
        $this->setOldKingdomUnits($kingdom);
        $this->setOldAttackingUnits($attackingKingdom, $unitsAttacking);

        $this->newAttackingUnits    = $this->oldAttackingUnits;
        $this->newDefenderBuildings = $this->oldDefenderBuildings;
        $this->newDefenderUnits     = $this->oldDefenderUnits;

        $this->siegeAttack($attackingKingdom, $kingdom);

        $this->unitsAttack($attackingKingdom, $kingdom);

        $this->returnSurvivingUnits($attackingKingdom, $kingdom);

        $this->createLogForAttacker($attackingKingdom, $kingdom);
        $this->createLogForDefender($attackingKingdom, $kingdom);
    }

    protected function unitsAttack(Kingdom $attackingKingdom, Kingdom $kingdom): void {
        $kingdomUnitHandler = $this->kingdomUnitHandler->setAttackingUnits($this->newAttackingUnits);

        $kingdomUnitHandler->attackUnits($kingdom, $attackingKingdom->id);

        $this->mergeAttackerUnits($kingdomUnitHandler->getAttackingUnits());

        $this->mergeDefenderUnits($kingdomUnitHandler->getDefenderUnits());

        $healingAmount = $this->getHealingAmount($this->newDefenderUnits);

        if ($healingAmount <= 0) {
            $this->newDefenderUnits = $this->healUnits($this->newDefenderUnits, $this->oldDefenderUnits, $healingAmount);
        }

        $healingAmount = $this->getHealingAmount($this->newAttackingUnits);

        if ($healingAmount <= 0) {
            $this->newAttackingUnits = $this->healUnits($this->newAttackingUnits, $this->oldAttackingUnits, $healingAmount);
        }
    }

    protected function mergeAttackerUnits($newAttackingUnits): void {
        foreach ($newAttackingUnits as $attackingUnit) {
            $index = array_search($attackingUnit['unit_id'], array_column($this->newAttackingUnits, 'unit_id'));

            if ($index !== false) {
                $this->newAttackingUnits[$index] = $attackingUnit;
            } else {
                $this->newAttackingUnits[] = $attackingUnit;
            }
        }
    }

    protected function mergeDefenderUnits($defenderUnits): void {
        foreach ($defenderUnits as $defenderUnit) {
            $index = array_search($defenderUnit['unit_id'], array_column($this->newDefenderUnits, 'unit_id'));

            if ($index !== false) {
                $this->newDefenderUnits[$index] = $defenderUnit;
            } else {
                $this->newDefenderUnits[] = $defenderUnit;
            }
        }
    }

    protected function mergeDefenderBuildings($newBuildings): void {
        foreach ($newBuildings as $building) {
            $index = array_search($building['name'], array_column($this->newDefenderBuildings, 'name'));

            if ($index !== false) {
                $this->newDefenderBuildings[$index] = $building;
            } else {
                $this->newDefenderBuildings[] = $building;
            }
        }
    }

    /**
     * Siege Attack.
     *
     * - Rams attack walls and farms
     * - Trebuchets attack all buildings and units.
     * - Cannons attack all buildings and units.
     * - Calculate the new kingdom morale
     * - Heal remaining units if possible.
     *
     * @param Kingdom $attackingKingdom
     * @param Kingdom $kingdom
     * @return void
     */
    protected function siegeAttack(Kingdom $attackingKingdom, Kingdom $kingdom): void {
        $this->currentMorale = $kingdom->current_morale;

        $kingdomSiegeHandler = $this->kingdomSiegeHandler->setAttackingUnits($this->oldAttackingUnits);

        $damageReduction = $this->getTotalDamageReduction($kingdom);

        $kingdom         = $kingdomSiegeHandler->handleRams($attackingKingdom, $kingdom, $damageReduction);
        $kingdom         = $kingdomSiegeHandler->handleTrebuchets($attackingKingdom, $kingdom, $damageReduction);
        $kingdom         = $kingdomSiegeHandler->handleCannons($attackingKingdom, $kingdom, $damageReduction);

        $newMorale = $this->calculateNewMorale($kingdom, $kingdom->current_morale);

        $kingdom->update([
            'current_morale' => $newMorale
        ]);

        $this->mergeAttackerUnits($kingdomSiegeHandler->getNewAttackingUnits());
        $this->mergeDefenderBuildings($kingdomSiegeHandler->getNewBuildings());
        $this->mergeDefenderUnits($kingdomSiegeHandler->getNewUnits());

        $healingAmount = $this->getHealingAmount($this->newDefenderUnits);

        if ($healingAmount <= 0) {
            $this->newDefenderUnits = $this->healUnits($this->newDefenderUnits, $this->oldDefenderUnits, $healingAmount);
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
        $damageReduction    = 0.05;
        $totalDefence       = $kingdom->fetchKingdomDefenceBonus();
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
