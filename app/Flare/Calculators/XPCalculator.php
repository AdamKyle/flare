<?php

namespace App\Flare\Calculators;

use App\Flare\Models\Monster;

class XPCalculator {

    public function fetchXPFromMonster(Monster $monster, int $characterLevel, float $xpReduction = 0.0) {
        if ($monster->max_level === 0) {
            // Always Just give.
            $xp = ($xpReduction !==  0.0 ? ($monster->xp - ($monster->xp * $xpReduction)) : $monster->xp);
        } else if ($characterLevel < $monster->max_level) {
            // So the monster has a max exp level and the character is below it, so they get full xp.
            $xp = ($xpReduction !==  0.0 ? ($monster->xp - ($monster->xp * $xpReduction)) : $monster->xp);
        } else if ($characterLevel > $monster->max_level) {
            // So the monster has a max exp level and the character is above it, so they get 1/3rd xp.
            $xp = ($xpReduction !==  0.0 ? (3.3333 - (3.3333 * $xpReduction)) : 3.3333);
        }

        return $xp;
    }

}