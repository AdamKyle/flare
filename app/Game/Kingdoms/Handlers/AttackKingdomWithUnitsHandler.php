<?php

namespace App\Game\Kingdoms\Handlers;


use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomUnit;
use Facades\App\Flare\Values\UserOnlineValue;
use App\Game\Kingdoms\Traits\CalculateMorale;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class AttackKingdomWithUnitsHandler {

    use CalculateMorale;

    /**
     * @var KingdomSiegeHandler $kingdomSiegeHandler
     */
    private KingdomSiegeHandler $kingdomSiegeHandler;

    /**
     * @var KingdomUnitHandler $kingdomUnitHandler
     */
    private KingdomUnitHandler $kingdomUnitHandler;

    /**
     * @var KingdomAirshipHandler $kingdomAirshipHandler
     */
    private KingdomAirshipHandler $kingdomAirshipHandler;

    /**
     * @var SettlerHandler $settlerHandler
     */
    private SettlerHandler $settlerHandler;

    /**
     * @var AttackLogHandler $attackLogHandler
     */
    private AttackLogHandler $attackLogHandler;

    /**
     * @var ReturnSurvivingUnitHandler $returnSurvivingUnitHandler
     */
    private ReturnSurvivingUnitHandler $returnSurvivingUnitHandler;

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
     * @param KingdomSiegeHandler $kingdomSiegeHandler
     * @param KingdomUnitHandler $kingdomUnitHandler
     * @param KingdomAirshipHandler $kingdomAirshipHandler
     * @param SettlerHandler $settlerHandler
     * @param AttackLogHandler $attackLogHandler
     * @param ReturnSurvivingUnitHandler $returnSurvivingUnitHandler
     */
    public function __construct(KingdomSiegeHandler $kingdomSiegeHandler,
                                KingdomUnitHandler $kingdomUnitHandler,
                                KingdomAirshipHandler $kingdomAirshipHandler,
                                SettlerHandler $settlerHandler,
                                AttackLogHandler $attackLogHandler,
                                ReturnSurvivingUnitHandler $returnSurvivingUnitHandler,
    ) {
        $this->kingdomSiegeHandler        = $kingdomSiegeHandler;
        $this->kingdomUnitHandler         = $kingdomUnitHandler;
        $this->kingdomAirshipHandler      = $kingdomAirshipHandler;
        $this->settlerHandler             = $settlerHandler;
        $this->attackLogHandler           = $attackLogHandler;
        $this->returnSurvivingUnitHandler = $returnSurvivingUnitHandler;
    }

    /**
     * Attack the defending kingdom with units.
     *
     * @param Kingdom $kingdom
     * @param Kingdom $attackingKingdom
     * @param array $unitsAttacking
     * @return void
     */
    public function attackKingdomWithUnits(Kingdom $kingdom, Kingdom $attackingKingdom, array $unitsAttacking): void {
        $this->currentMorale = $kingdom->current_morale;

        $this->setOldKingdomBuildings($kingdom);
        $this->setOldKingdomUnits($kingdom);
        $this->setOldAttackingUnits($attackingKingdom, $unitsAttacking);

        $this->newAttackingUnits    = $this->oldAttackingUnits;
        $this->newDefenderBuildings = $this->oldDefenderBuildings;
        $this->newDefenderUnits     = $this->oldDefenderUnits;

        $this->airshipAttack($attackingKingdom, $kingdom);
        $this->siegeAttack($attackingKingdom, $kingdom);
        $this->unitsAttack($attackingKingdom, $kingdom);

        $originalOwner = $kingdom->character;

        $tookKingdom = $this->takeKingdom($attackingKingdom, $kingdom);

        $kingdom = $kingdom->refresh();

        $attackLogHandler = $this->attackLogHandler->setCurrentMorale($this->currentMorale)
                                                   ->setOldAttackingUnits($this->oldAttackingUnits)
                                                   ->setNewAttackingUnits($this->newAttackingUnits)
                                                   ->setOldDefenderUnits($this->oldDefenderUnits)
                                                   ->setNewDefenderUnits($this->newDefenderUnits)
                                                   ->setOldDefenderBuildings($this->oldDefenderBuildings)
                                                   ->setNewDefenderBuildings($this->newDefenderBuildings);

        if ($tookKingdom) {
            $attackLogHandler->createLogForAttacker($attackingKingdom, $kingdom, true);

            $kingdomName   = $kingdom->name;
            $mapName       = $kingdom->gameMap->name;
            $x             = $kingdom->x_position;
            $y             = $kingdom->y_position;
            $characterName = $attackingKingdom->character->name;

            event(new GlobalMessageEvent($characterName . ' has taken the kingdom: ' . $kingdomName .
            ' on the plane: ' . $mapName . ' at (X/Y): ' . $x . '/' . $y . ' and is now the rightful ruler!'));

            if (!is_null($originalOwner)) {
                if (UserOnlineValue::isOnline($originalOwner->user)) {
                    event(new ServerMessageEvent($originalOwner->user, 'You lost your kingdom: ' . $kingdomName .
                        ' on plane: ' . $mapName . ' at (X/Y): ' . $x . '/' . $y . ' to: ' . $characterName . ' who now the rightful owner'));
                }
            }

            return;
        }


        $this->returnSurvivingUnitHandler->setNewAttackingUnits($this->newAttackingUnits)
                                         ->returnSurvivingUnits($attackingKingdom, $kingdom);



        $attackLogHandler->createLogForAttacker($attackingKingdom, $kingdom);
        $attackLogHandler->createLogForDefender($attackingKingdom, $kingdom);
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
     * Airship Attack.
     *
     * - Attack enemy airships first - this is where you can take damage.
     * - Attack buildings - you take no damage
     *   - Both Attack Bonuses and Buffs are included here.
     * - Calculate the new kingdom morale
     * - Heal remaining units if possible.
     *
     * @param Kingdom $attackingKingdom
     * @param Kingdom $kingdom
     * @return void
     */
    protected function airshipAttack(Kingdom $attackingKingdom, Kingdom $kingdom): void {
        $this->currentMorale = $kingdom->current_morale;

        $kingdomAirshipHandler = $this->kingdomAirshipHandler->setAttackingUnits($this->oldAttackingUnits);

        $damageReduction = $this->getTotalDamageReduction($kingdom);
        $kingdom         = $kingdomAirshipHandler->handleAirships($attackingKingdom, $kingdom, $damageReduction);

        $newMorale = $this->calculateNewMorale($kingdom, $kingdom->current_morale);

        $kingdom->update([
            'current_morale' => $newMorale
        ]);

        $this->mergeAttackerUnits($kingdomAirshipHandler->getNewAttackingUnits());
        $this->mergeDefenderBuildings($kingdomAirshipHandler->getNewBuildings());
        $this->mergeDefenderUnits($kingdomAirshipHandler->getNewUnits());

        $healingAmount = $this->getHealingAmount($this->newDefenderUnits);

        if ($healingAmount <= 0) {
            $this->newDefenderUnits = $this->healUnits($this->newDefenderUnits, $this->oldDefenderUnits, $healingAmount);
        }
    }

    /**
     * Attack the kingdom with units.
     *
     * @param Kingdom $attackingKingdom
     * @param Kingdom $kingdom
     * @return void
     */
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

    /**
     * Attempt to take the kingdom.
     *
     * Returns a boolean based on if we took the kingdom or not.
     * Existing units are automatically transitioned to said kingdom.
     *
     * @param Kingdom $attackingKingdom
     * @param Kingdom $defendingKingdom
     * @return bool
     */
    protected function takeKingdom(Kingdom $attackingKingdom, Kingdom $defendingKingdom) {
        $defendingKingdom = $this->settlerHandler->attemptToSettleKingdom($attackingKingdom, $defendingKingdom, $this->newAttackingUnits);

        if ($defendingKingdom->character_id === $attackingKingdom->character_id) {
            return true;
        }

        $newUnits = $this->settlerHandler->getNewAttackingUnits();

        if (!empty($newUnits)) {
            $this->mergeAttackerUnits($this->settlerHandler->getNewAttackingUnits());
        }

        return false;
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

    /**
     * Get the total amount of healing.
     *
     * @param array $units
     * @return float
     */
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

    /**
     * Heal the units.
     *
     * @param array $newUnits
     * @param array $oldUnits
     * @param float $healAmount
     * @return array
     */
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

    /**
     * Merge the new attacking units with the existing ones.
     *
     * @param $newAttackingUnits
     * @return void
     */
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

    /**
     * Merge the new defending units with the existing ones.
     *
     * @param $defenderUnits
     * @return void
     */
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

    /**
     * Merge the existing defending buildings with the new ones.
     *
     * @param $newBuildings
     * @return void
     */
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
     * Get the original amount of units.
     *
     * @param string $name
     * @param array $units
     * @return int
     */
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
