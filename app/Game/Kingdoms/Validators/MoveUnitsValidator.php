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
            if (! isset($unitsToMove['kingdom_id'], $unitsToMove['unit_id'], $unitsToMove['amount'])) {
                return false;
            }

            $kingdomId = filter_var($unitsToMove['kingdom_id'], FILTER_VALIDATE_INT);
            $unitId = filter_var($unitsToMove['unit_id'], FILTER_VALIDATE_INT);
            $amount = filter_var($unitsToMove['amount'], FILTER_VALIDATE_INT);

            if ($kingdomId === false || $unitId === false || $amount === false || $amount < 1) {
                return false;
            }

            $kingdom = $character->kingdoms()->find($kingdomId);

            if (is_null($kingdom)) {
                return false;
            }

            if ($kingdom->game_map_id !== $kingdomToMoveTo->game_map_id) {
                return false;
            }

            $unit = $kingdom->units()->find($unitId);

            if (is_null($unit)) {
                return false;
            }

            if ($unit->amount < $amount) {
                return false;
            }
        }

        return true;
    }
}
