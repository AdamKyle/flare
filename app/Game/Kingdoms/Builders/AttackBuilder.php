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

        if (!is_null($character)) {
            $query = $query->where('character_id', '!=', $character->id);
        }

        $this->defender = $query->first();

        return $this;
    }

    /**
     * Returns the defending character or null.
     *
     * @return Character
     */
    public function getDefendingCharacter(): Character {
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
