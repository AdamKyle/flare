<?php

namespace App\Game\Kingdoms\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitMovementQueue;

class KingdomHandler {

    /**
     * @var Kingdom $kingdom
     */
    private $kingdom;

    /**
     * Sets the kingdom.
     *
     * @param Kingdom $kingdom
     * @return $this
     */
    public function setKingdom(Kingdom $kingdom): KingdomHandler {
        $this->kingdom = $kingdom;

        return $this;
    }

    /**
     * Decreases the kingdom morale if buildings have fallen.
     *
     * @return KingdomHandler
     */
    public function decreaseMorale(): KingdomHandler {
        $totalDecrease = 0;

        foreach ($this->kingdom->buildings as $building) {
            if ($building->durability === 0) {
                $totalDecrease += $building->morale_decrease;
            }
        }

        $currentMorale = $this->kingdom->current_morale;
        $newMorale     = $currentMorale - $totalDecrease;

        $this->kingdom->update([
            'current_morale' => $newMorale > 0 ? $newMorale : 0,
        ]);

        return $this;
    }

    /**
     * Update the defenders morale.
     *
     * Subtracts the settlers morale decrease from the defenders morale.
     *
     * @param Kingdom $defender
     * @param GameUnit $settlerUnit
     * @return Kingdom
     */
    public function updateDefendersMorale(Kingdom $defender, GameUnit $settlerUnit): Kingdom {
        $currentMorale = $defender->current_morale - $settlerUnit->reduces_morale_by;

        $defender->current_morale = $currentMorale < 0 ? 0 : $currentMorale;

        $defender->save();

        return $defender->refresh();
    }

    /**
     * Take the kingdom from the defending player.
     *
     * Assigns the kingdom to the attacking player.
     *
     * Assigns any left over units to the newly taken kingdom as well.
     *
     * The settler is assumed to become a new "adviser".
     *
     * @param UnitMovementQueue $unitMovement
     * @param Character $character
     * @param array $newRegularUnits
     * @param array $newSiegeUnits
     */
    public function takeKingdom(UnitMovementQueue $unitMovement, Character $character, array $newUnits): void {
        $kingdom = $unitMovement->toKingdom;

        $kingdom->update([
            'character_id' => $character->id,
        ]);

        foreach ($newUnits as $unitInfo) {
            if (!$unitInfo['settler']) {
                $unit = $kingdom->units()->where('game_unit_id', $unitInfo['unit_id'])->first();

                if (!is_null($unit)) {
                    $unit->update([
                        'amount' => $unit->amount + $unitInfo['amount']
                    ]);
                }
            }
        }
    }

    /**
     * Fetches the refreshed kingdom.
     *
     * @return Kingdom
     */
    public function getKingdom(): Kingdom {
        return $this->kingdom->refresh();
    }

}
