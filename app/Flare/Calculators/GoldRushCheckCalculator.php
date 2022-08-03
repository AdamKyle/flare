<?php

namespace App\Flare\Calculators;

use Facades\App\Flare\RandomNumber\RandomNumberGenerator;
use App\Flare\Models\Monster;

class GoldRushCheckCalculator {

    /**
     * Fetches the gold rush check chance.
     *
     * Fetches the adventure bonus if applicable and applies it to the looting bonus against the monster's drop check.
     *
     * If true, the check passed and the character should be rewarded.
     *
     * @param Monster $monster
     * @param float $gameMapBonus
     * @return bool
     */
    public function fetchGoldRushChance(Monster $monster, float $gameMapBonus = 0.0) {
        $bonus = $gameMapBonus;

        $roll = RandomNumberGenerator::generateRandomNumber(1, 50 ,1 ,100);
        $roll += ceil($roll * $bonus);

        return $roll > 95;
    }
}
