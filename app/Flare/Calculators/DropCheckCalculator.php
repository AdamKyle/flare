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
     * @param float $gameMapBonus
     * @param Adventure|null $adventure | null
     * @return bool
     */
    public function fetchDropCheckChance(Monster $monster, float $lootingChance = 0.0, float $gameMapBonus = 0.0, Adventure $adventure = null): bool {
        $adventureBonus = $this->getAdventureBonus($adventure);

        if ($monster->drop_check <= 0.0 && is_null($adventure)) {
            return false;
        }

        if ($adventureBonus >= 1.0 || $monster->drop_check > 1.0) {
            return true;
        }

        $bonus = $lootingChance + $adventureBonus + $gameMapBonus;

        if ($bonus >= 1.0) {
            return true;
        }

        $roll = rand(1, 1000);
        $roll += round($roll * $bonus);
        $dc   = round((1000 - (1000 * $monster->drop_check)));

        return $roll > $dc;
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
    public function fetchQuestItemDropCheck(Monster $monster, float $lootingChance = 0.0, float $gameMapBonus = 0.0, Adventure $adventure = null): bool {
        $adventureBonus = $this->getAdventureBonus($adventure);
        $totalBonus     = $adventureBonus + $lootingChance + $gameMapBonus;

        if ($monster->quest_item_drop_chance <= 0.0 && is_null($adventure)) {
            return false;
        }

        if ($monster->quest_item_drop_chance >= 1.0 || $monster->quest_item_drop_chance >= 1.0) {
            return true;
        }

        if ($totalBonus >= 1.0) {
            return true;
        }

        $roll = rand(1, 1000);
        $roll = round($roll + $roll * $totalBonus);
        $dc   = round((1000 - (1000 * $monster->drop_check)));

        return $roll > $dc;
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
