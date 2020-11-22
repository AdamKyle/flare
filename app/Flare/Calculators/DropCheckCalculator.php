<?php

namespace App\Flare\Calculators;

use App\Flare\Models\Adventure;
use App\Flare\Models\Monster;

class DropCheckCalculator {

    /**
     * Fetches the drop check chance.
     * 
     * Fecthes the adventure bonus if applies and applies it to the looting bonus agains the monsters drop check.
     * 
     * If true, the check passed and the character should be rewarded.
     * 
     * @param Monster $monster
     * @param float $lootingChance | 0.0
     * @param Adaventure $adventure | null
     * @return bool
     */
    public function fetchDropCheckChance(Monster $monster, float $lootingChance = 0.0, Adventure $adventure = null): bool {
        $adventureBonus = $this->getAdventureBonus($adventure);

        if ($adventureBonus >= 1) {
            return true;
        }

        return (rand(1, 100) * (1 + ($lootingChance + $adventureBonus)))  > (100 - (100 * $monster->drop_check));
    }

    protected function getAdventureBonus(Adventure $adventure = null): float {
        if (!is_null($adventure)) {
            return $adventure->item_find_chance;
        }

        return 0.0;
    }
}