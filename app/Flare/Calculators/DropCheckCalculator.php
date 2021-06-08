<?php

namespace App\Flare\Calculators;

use App\Flare\Models\Adventure;
use App\Flare\Models\Monster;

class DropCheckCalculator {

    /**
     * Determines if the player can get a drop from the monster.
     *
     * Fetches the adventure bonus if applies and applies it to the looting bonus against the monsters drop check.
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

    /**
     * Determines if the player can get a quest item from the monster.
     *
     * Fetches the adventure bonues, if applies and applies it to the looting bonus against the monster quest_item_drop_chance.
     *
     * @param Monster $monster
     * @param Adventure|null $adventure
     * @return bool
     */
    public function fetchQuestItemDropCheck(Monster $monster, float $lootingChance = 0.0, Adventure $adventure = null): bool {
        $adventureBonus = $this->getAdventureBonus($adventure);
        $totalBonus     = $adventureBonus + $lootingChance;

        if ($totalBonus < 1) {
            $totalBonus = 1 + $totalBonus;
        }

        if ($totalBonus >= 1) {
            return true;
        }

        if ($monster->quest_item_drop_chance >= 1) {
            return true;
        }

        return (rand(1, 100) * $totalBonus)  > (100 - (100 * $monster->quest_item_drop_chance));
    }

    /**
     * Gets the adventure bonus.
     *
     * @param Adventure|null $adventure
     * @return float
     */
    protected function getAdventureBonus(Adventure $adventure = null): float {
        if (!is_null($adventure)) {
            return $adventure->item_find_chance;
        }

        return 0.0;
    }
}
