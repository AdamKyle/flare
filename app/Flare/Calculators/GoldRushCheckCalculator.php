<?php

namespace App\Flare\Calculators;

use App\Flare\Models\Adventure;
use App\Flare\Models\Monster;

class GoldRushCheckCalculator {

    public function fetchGoldRushChance(Monster $monster, float $lootingChance = 0.0, Adventure $adventure = null) {
        $adventureBonus = $this->getAdventureGoldrushChance($adventure);

        return (rand(1, 100) * (1 + ($lootingChance + $adventureBonus))) > ((100 * $monster->drop_check) + 100);
    }

    protected function getAdventureGoldrushChance(Adventure $adventure = null): float {
        if (!is_null($adventure)) {
            return $adventure->gold_rush_chance;
        }

        return 0.0;
    }
}