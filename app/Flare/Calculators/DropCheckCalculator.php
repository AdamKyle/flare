<?php

namespace App\Flare\Calculators;

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
        $totalChance = $monster->drop_check + $lootingChance + $gameMapBonus;

        if ($totalChance >= 1) {
            return true;
        }

        if ($characterLevel < 12 && $lootingChance < .10) {
            $totalChance = .65;

            return $this->canGetReward(100, $totalChance);
        }

        return $this->canGetReward(100, $totalChance);
    }

    /**
     * Can we get more difficult items like mythics and specific quest items.
     *
     * @param float $lootingChance
     * @return bool
     */
    public function fetchDifficultItemChance(float $lootingChance = 0.0, int $max = 1000000): bool {
        return $this->canGetReward($max, $lootingChance);
    }

    /**
     * Determines if the player can get a quest item from the monster.
     *
     * @param Monster $monster
     * @param float $lootingChance
     * @param float $gameMapBonus
     * @return bool
     */
    public function fetchQuestItemDropCheck(Monster $monster, float $lootingChance = 0.0, float $gameMapBonus = 0.0): bool {
        $totalBonus = $lootingChance + $gameMapBonus + $monster->drop_check;

        if ($totalBonus >= 1) {
            return true;
        }

        return $this->canGetReward(100, $totalBonus);
    }

    /**
     * Can we get the actual reward?
     *
     * - The base chance is calculated based on a 1/x chance where x is the max of a random number.
     * - We then add additional bonuses to this chance and apply a condition based on a true random number.
     *
     * @param int $max
     * @param float $additionalChanceBonus
     * @return bool
     */
    protected function canGetReward(int $max = 100, float $additionalChanceBonus = 0.0): bool {
        $baseChance = 1 / $max;

        $chanceOfSuccess = $baseChance + $additionalChanceBonus;

        return $this->attemptToGainReward($chanceOfSuccess);
    }

    /**
     * Based on chance of success and roll of the dice we attempt to gain the reward.
     *
     * @param float $chanceOfSuccess
     * @return bool
     */
    private function attemptToGainReward(float $chanceOfSuccess): bool {

        $roll = RandomNumberGenerator::generateTureRandomNumber(0, 1);

        if ($roll <= $chanceOfSuccess) {
            return true;
        }

        return false;
    }
}
