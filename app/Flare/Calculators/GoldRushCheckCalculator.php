<?php

namespace App\Flare\Calculators;

use Facades\App\Flare\RandomNumber\LotteryRandomNumberGenerator;
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
    public function fetchGoldRushChance(float $gameMapBonus = 0.0) {
        $bonus = $gameMapBonus;

        $randomAmount = rand(1, 100);
        $roll         = LotteryRandomNumberGenerator::generateNumber($randomAmount);

        $roll += ceil($roll * $bonus);

        return $roll > 99;
    }
}
