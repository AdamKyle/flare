<?php

namespace App\Game\Kingdoms\Validators;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;

class MoveUnitsValidator
{
    private array $unitsToMove;

    public function setUnitsToMove(array $unitsToMove): MoveUnitsValidator
    {
        $this->unitsToMove = $unitsToMove;

        return $this;
    }

    /**
     * Validate the data.
     *
     * - Make sure the character owns the kingdoms the units come from.
     * - Make sure the two kingdoms, to go to and to go from, are on the same plane.
     * - Make sure those kingdoms own the units.
     * - Make sure the amount of units does not exceed what they have on hand.
     */
    public function isValid(Character $character, Kingdom $kingdomToMoveTo): bool
    {
        foreach ($this->unitsToMove as $unitsToMove) {
            $kingdom = $character->kingdoms()->find($unitsToMove['kingdom_id']);

            if (is_null($kingdom)) {
                return false;
            }

            if ($kingdom->game_map_id !== $kingdomToMoveTo->game_map_id) {
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
