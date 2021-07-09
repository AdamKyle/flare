<?php

namespace App\Game\Kingdoms\Builders;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitMovementQueue;

class AttackBuilder {

    /**
     * @var mixed $defender
     */
    private $defender;

    public function __construct() {

    }

    /**
     * Sets the defending kingdom.
     *
     * @param UnitMovementQueue $unitMovement
     * @param int $defenderId
     * @param Character|null $character
     * @return $this
     */
    public function setDefender(UnitMovementQueue $unitMovement, int $defenderId, Character $character = null): AttackBuilder {

        $query = Kingdom::where('id', $defenderId)
                        ->where('x_position', $unitMovement->moving_to_x)
                        ->where('y_position', $unitMovement->moving_to_y);

        $kingdom = $query->first();

        if (!is_null($character) && !is_null($kingdom->character_id)) {
            if ($kingdom->character_id === $character->id) {
                return $this;
            }
        }

        $this->defender = $kingdom;

        return $this;
    }

    /**
     * Returns the defending character or null.
     *
     * @return Mixed
     */
    public function getDefendingCharacter() {
        if (is_null($this->getDefender()->character_id)) {
            return null;
        }

        return $this->getDefender()->character;
    }

    /**
     * Returns either the defenders kingdom or null.
     *
     * @return mixed
     */
    public function getDefender() {
        return $this->defender;
    }
}
