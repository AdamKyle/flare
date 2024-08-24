<?php

namespace App\Game\Kingdoms\Handlers;

use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomLog;
use App\Flare\Values\KingdomLogStatusValue;
use App\Game\Kingdoms\Service\UpdateKingdom;
use App\Game\Messages\Events\ServerMessageEvent;

class AttackLogHandler
{
    private UpdateKingdom $updateKingdom;

    private float $currentMorale = 0.0;

    private array $oldDefenderBuildings;

    private array $newDefenderBuildings;

    private array $oldDefenderUnits;

    private array $newDefenderUnits;

    private array $oldAttackingUnits;

    private array $newAttackingUnits;

    public function __construct(UpdateKingdom $updateKingdom)
    {
        $this->updateKingdom = $updateKingdom;
    }

    /**
     * Set the current morale for the log.
     *
     * @return $this
     */
    public function setCurrentMorale(float $currentMorale): AttackLogHandler
    {
        $this->currentMorale = $currentMorale;

        return $this;
    }

    /**
     * Set the old defenders building for the log.
     *
     * @return $this
     */
    public function setOldDefenderBuildings(array $oldDefenderBuildings): AttackLogHandler
    {
        $this->oldDefenderBuildings = $oldDefenderBuildings;

        return $this;
    }

    /**
     * Set the new defender buildings for the log.
     *
     * @return $this
     */
    public function setNewDefenderBuildings(array $newDefenderBuildings): AttackLogHandler
    {
        $this->newDefenderBuildings = $newDefenderBuildings;

        return $this;
    }

    /**
     * Set old defending units.
     *
     * @return $this
     */
    public function setOldDefenderUnits(array $oldDefenderUnits): AttackLogHandler
    {
        $this->oldDefenderUnits = $oldDefenderUnits;

        return $this;
    }

    /**
     * Set new defending units.
     *
     * @return $this
     */
    public function setNewDefenderUnits(array $newDefenderUnits): AttackLogHandler
    {
        $this->newDefenderUnits = $newDefenderUnits;

        return $this;
    }

    /**
     * Set the old attacking units.
     *
     * @return $this
     */
    public function setOldAttackingUnits(array $oldAttackingUnits): AttackLogHandler
    {
        $this->oldAttackingUnits = $oldAttackingUnits;

        return $this;
    }

    /**
     * Set the new attacking units.
     *
     * @return $this
     */
    public function setNewAttackingUnits(array $newAttackingUnits): AttackLogHandler
    {
        $this->newAttackingUnits = $newAttackingUnits;

        return $this;
    }

    /**
     * Create a log for the attacker.
     */
    public function createLogForAttacker(Kingdom $attackingKingdom, Kingdom $defenderKingdom, bool $tookKingdom = false): void
    {
        $logDetails = $this->createBaseAttributes($attackingKingdom, $defenderKingdom, $tookKingdom);

        $logDetails['character_id'] = $attackingKingdom->character_id;
        $logDetails['attacking_character_id'] = $attackingKingdom->character_id;

        KingdomLog::create($logDetails);

        event(new ServerMessageEvent($attackingKingdom->character->user, 'Your attack has landed on kingdom: '.
            $defenderKingdom->name.' on the plane: '.$defenderKingdom->gameMap->name.' At (X/Y): '.$defenderKingdom->x_position.'/'.$defenderKingdom->y_position.
            ' you have a new attack log.'));

        $character = $attackingKingdom->character->refresh();

        $this->updateKingdom->updateKingdomAllKingdoms($character);
        $this->updateKingdom->updateKingdomLogs($character, true);

    }

    /**
     * Create a log for the defender.
     */
    public function createLogForDefender(Kingdom $attackingKingdom, Kingdom $defenderKingdom): void
    {
        if ($defenderKingdom->npc_owned) {
            return;
        }

        $logDetails = $this->createBaseAttributes($attackingKingdom, $defenderKingdom);

        $logDetails['character_id'] = $defenderKingdom->character_id;
        $logDetails['attacking_character_id'] = $attackingKingdom->character_id;

        KingdomLog::create($logDetails);

        event(new ServerMessageEvent($defenderKingdom->character->user, $attackingKingdom->character->name.' has attacked your kingdom: '.
            $defenderKingdom->name.' on the plane: '.$defenderKingdom->gameMap->name.' At (X/Y): '.$defenderKingdom->x_position.'/'.$defenderKingdom->y_position.
            ' you have a new attack log.'));

        $character = $defenderKingdom->character->refresh();

        $this->updateKingdom->updateKingdomAllKingdoms($character);
        $this->updateKingdom->updateKingdomLogs($character, true);
    }

    /**
     * Create the base attributes of the log.
     */
    protected function createBaseAttributes(Kingdom $attackingKingdom, Kingdom $defenderKingdom, bool $tookKingdom = false): array
    {

        $newMorale = $this->currentMorale - $defenderKingdom->current_morale;

        return [
            'to_kingdom_id' => $defenderKingdom->id,
            'from_kingdom_id' => $attackingKingdom->id,
            'status' => $tookKingdom ? KingdomLogStatusValue::TAKEN : KingdomLogStatusValue::ATTACKED,
            'old_buildings' => $this->oldDefenderBuildings,
            'new_buildings' => $this->newDefenderBuildings,
            'old_units' => $this->oldDefenderUnits,
            'new_units' => $this->newDefenderUnits,
            'units_sent' => $this->oldAttackingUnits,
            'units_survived' => $this->newAttackingUnits,
            'morale_loss' => max($newMorale, 0),
            'published' => true,
        ];
    }
}
