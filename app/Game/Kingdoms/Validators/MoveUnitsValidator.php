<?php

namespace App\Game\Kingdoms\Validators;

use App\Flare\Models\Character;

class MoveUnitsValidator {


    /**
     * @var array $unitsToMove
     */
    private array $unitsToMove;

    /**
     * @param array $unitsToMove
     * @return MoveUnitsValidator
     */
    public function setUnitsToMove(array $unitsToMove): MoveUnitsValidator {
        $this->unitsToMove = $unitsToMove;

        return $this;
    }

    /**
     * Validate the data.
     *
     * - Make sure the character owns the kingdoms the units come from.
     * - Make sure those kingdoms own the units.
     * - Make sure the amount of units does not exceed what they have on hand.
     *
     * @param Character $character
     * @return bool
     */
    public function isValid(Character $character): bool {
        foreach ($this->unitsToMove as $unitsToMove) {
            $kingdom = $character->kingdoms()->find($unitsToMove['kingdom_id']);

            if (is_null($kingdom)) {
                return false;
            }

            $unit = $kingdom->units()->find($unitsToMove['unit_id']);

            if (is_null($unit)) {
                return false;
            }

            if ($unit->amount < $unitsToMove['amount']) {
                return false;
            }
        }

        return true;
    }
}
