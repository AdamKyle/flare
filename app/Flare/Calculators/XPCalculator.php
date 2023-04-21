<?php

namespace App\Flare\Calculators;

use App\Flare\Models\Monster;

class XPCalculator {


    /**
     * Calculates the total xp
     *
     * If the character is above the max level, we return the monster xp minus any reductions for skill training.
     *
     * If the character is over the max level, we return 3.333 xp minus any reductions for skill training.
     *
     * @param Monster $monster
     * @param int $characterLevel
     * @param float $xpReduction | 0.0
     * @return int
     */
    public function fetchXPFromMonster(Monster $monster, int $characterLevel, float $xpReduction = 0.0): int {
        $xp = 0;

        if ($characterLevel < $monster->max_level) {
            // The monster has a max exp level and the character is below it, so they get full xp.
            $xp = ($xpReduction !==  0.0 ? ($monster->xp - ($monster->xp * $xpReduction)) : $monster->xp);
        } else if ($characterLevel >= $monster->max_level) {
            // The monster has a max exp level and the character is above it or equal to it, so they get 1/3rd xp.
            $xp = ($xpReduction !==  0.0 ? (3.3333 - (3.3333 * $xpReduction)) : 3.3333);
        }

        return ceil($xp);
    }

}
