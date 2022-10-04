<?php

namespace App\Flare\Calculators;

use App\Flare\Models\Adventure;
use App\Flare\Models\Monster;
use Facades\App\Flare\RandomNumber\RandomNumberGenerator;

class DropCheckCalculator {

    /**
     * Determines if the player can get a drop from the monster.
     *
     * Fetches the adventure bonus if applies and applies it to the looting bonus against the monsters drop check.
     *
     * If true, the check passed and the character should be rewarded.
     *
     * @param Monster $monster
     * @param int $characterLevel
     * @param float $lootingChance | 0.0
     * @param float $gameMapBonus
     * @return bool
     */
    public function fetchDropCheckChance(Monster $monster, int $characterLevel, float $lootingChance = 0.0, float $gameMapBonus = 0.0): bool {
        if ($monster->drop_check <= 0.0) {
            return false;
        }

        $bonus = $lootingChance + $gameMapBonus;

        if ($bonus >= 1) {
            return true;
        }

        if ($characterLevel < 10 && $lootingChance < .10) {
            $roll = RandomNumberGenerator::generateRandomNumber(100, 50, 1, 500);
        } else {
            $roll = RandomNumberGenerator::generateRandomNumber(1, 50, 1, 100);
        }

        $dc   = round((100 - (100 * ($monster->drop_check + $bonus))));

        return $roll >= $dc;
    }

    /**
     * Can we get a location drop?
     *
     * @param float $locationChance
     * @param float $lootingChance
     * @return bool
     */
    public function fetchLocationDropChance(float $locationChance, float $lootingChance): bool {
        $roll = RandomNumberGenerator::generateRandomNumber(1, 50 , 1, 100);
        $dc   = round((100 - (100 * ($locationChance + $lootingChance))));

        return $roll > $dc;
    }

    /**
     * Determines if the player can get a quest item from the monster.
     *
     * Fetches the adventure bonuses, if applies and applies it to the looting bonus against the monster quest_item_drop_chance.
     *
     * @param Monster $monster
     * @param float $lootingChance
     * @param float $gameMapBonus
     * @return bool
     */
    public function fetchQuestItemDropCheck(Monster $monster, float $lootingChance = 0.0, float $gameMapBonus = 0.0): bool {
        $totalBonus     = $lootingChance + $gameMapBonus;

        if ($monster->quest_item_drop_chance <= 0) {
            return false;
        }

        if ($monster->quest_item_drop_chance >= 1.0) {
            return true;
        }

        if ($totalBonus >= 1.0) {
            return true;
        }

        $roll = RandomNumberGenerator::generateRandomNumber(1, 50, 1, 100);;
        $roll = round($roll + $roll * $totalBonus);
        $dc   = round((100 - (100 * $monster->drop_check)));

        return $roll > $dc;
    }
}
