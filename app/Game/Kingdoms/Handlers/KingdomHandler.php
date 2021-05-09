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
     * @var TakeKingdomHandler $takeKingdomHandler
     */
    private $takeKingdomHandler;

    /**
     * KingdomHandler constructor.
     *
     * @param TakeKingdomHandler $takeKingdomHandler
     */
    public function __construct(TakeKingdomHandler $takeKingdomHandler) {
        $this->takeKingdomHandler = $takeKingdomHandler;
    }

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

            if ($building->current_durability === 0 || $building->current_durability === 0.0) {
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
     * Take the kingdom.
     *
     * @param Kingdom $defender
     * @param Character $character
     * @param array $survivingUnits
     * @return bool
     */
    public function takeKingdom(Kingdom $defender, Character $character, array $survivingUnits): bool {
        return $this->takeKingdomHandler->takeKingdom($defender, $character, $survivingUnits);
    }

    /**
     * Fetches the refreshed kingdom.
     *
     * @return Kingdom
     */
    public function getKingdom(): Kingdom {
        return $this->kingdom->refresh();
    }

    /**
     * gets the old kingdom for the log.
     *
     * @return array
     */
    public function getOldKingdom(): array {
        return $this->takeKingdomHandler->getOldKingdom();
    }

}
