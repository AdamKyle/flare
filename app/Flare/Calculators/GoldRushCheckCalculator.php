<?php

namespace App\Flare\Calculators;

use App\Flare\Models\Adventure;
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
     * @param Adventure|null $adventure | null
     * @return bool
     */
    public function fetchGoldRushChance(Monster $monster, float $gameMapBonus = 0.0, Adventure $adventure = null) {
        $adventureBonus = $this->getAdventureGoldRushChance($adventure);

        $bonus = $adventureBonus + $gameMapBonus;

        $roll = rand(1, 1000);
        $roll += ceil($roll * $bonus);

        return $roll > 975;
    }

    protected function getAdventureGoldRushChance(Adventure $adventure = null): float {
        if (!is_null($adventure)) {
            return $adventure->gold_rush_chance;
        }

        return 0.0;
    }
}
