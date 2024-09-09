<?php

namespace App\Flare\Calculators;

use App\Flare\Models\Monster;

class XPCalculator
{
    /**
     * Calculates the total xp
     *
     * If the character is above the max level, we return the monster xp minus any reductions for skill training.
     *
     * If the character is over the max level, we return 3.333 xp minus any reductions for skill training.
     *
     * @param  float  $xpReduction  | 0.0
     */
    public function fetchXPFromMonster(Monster $monster, int $characterLevel, float $xpReduction = 0.0): int
    {
        $xp = 0;

        if ($characterLevel < $monster->max_level) {
            // The monster has a max exp level and the character is below it, so they get full xp.
            $xp = ($xpReduction !== 0.0 ? ($monster->xp - ($monster->xp * $xpReduction)) : $monster->xp);
        } elseif ($characterLevel >= $monster->max_level) {
            // The monster has a max exp level and the character is above it or equal to it, so they get 1/3rd xp.
            $xp = ($xpReduction !== 0.0 ? $monster->xp * 0.333 : ($monster->xp - $monster->xp * $xpReduction) * 0.33);
        }

        return ceil($xp);
    }
}
