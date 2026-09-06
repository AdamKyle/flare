<?php

namespace App\Game\Core\Services;

use App\Flare\Models\Character;
use App\Game\Character\CharacterCreation\Calculators\BaseStatCalculator;
use App\Game\Reincarnate\Values\MaxReincarnationStats;

class CharacterStatRepairService
{
    /**
     * @param  BaseStatCalculator  $baseStatValue  Class-based Character base stat calculator.
     */
    public function __construct(private readonly BaseStatCalculator $baseStatValue) {}

    /**
     * Calculate the minimum reincarnation stat bonus a Character must have for their times reincarnated and max level.
     *
     * @param  Character  $character  Character whose minimum reincarnation bonus is being calculated.
     * @param  int  $maxLevel  Max level used by the calculation.
     * @return int Minimum reincarnation stat bonus required for the Character's times reincarnated and max level.
     */
    public function getMinimumReincarnationBonus(Character $character, int $maxLevel): int
    {
        $bonus = 0;

        for ($reincarnationCount = 0; $reincarnationCount < $character->times_reincarnated; $reincarnationCount++) {
            $stat = 10 + $bonus + ($maxLevel - 1);
            $bonus += intdiv($stat, 20);
            $bonus = min($bonus, MaxReincarnationStats::MAX_STATS);
        }

        return $bonus;
    }

    /**
     * Repair the Character's reincarnated stat increase when it is below the minimum required bonus.
     *
     * @param  Character  $character  Character being repaired.
     * @param  int  $maxLevel  Max level used by the repair calculation.
     * @return void Updates the Character's reincarnated stat increase in place when it is below the minimum; no direct return value.
     */
    public function repairReincarnationBonus(Character $character, int $maxLevel): void
    {
        $minimumBonus = $this->getMinimumReincarnationBonus($character, $maxLevel);

        if ($character->reincarnated_stat_increase >= $minimumBonus) {
            return;
        }

        $character->update([
            'reincarnated_stat_increase' => $minimumBonus,
        ]);
    }

    /**
     * Repair any of the Character's core stats that have fallen below their expected floor.
     *
     * @param  Character  $character  Character whose stats are being repaired.
     * @return void Updates any below-floor core stats on the Character in place; no direct return value.
     */
    public function repair(Character $character): void
    {
        $baseStats = ['str', 'dur', 'dex', 'chr', 'int', 'agi', 'focus'];
        $levelUps = max($character->level - 1, 0);
        $reincarnatedStatIncrease = $character->reincarnated_stat_increase;
        $updates = [];
        $baseStatValue = $this->baseStatValue->setClass($character->class);

        foreach ($baseStats as $stat) {
            $levelUpFloor = $character->damage_stat === $stat ? $levelUps * 2 : $levelUps;
            $floor = min($baseStatValue->{$stat}() + $reincarnatedStatIncrease + $levelUpFloor, MaxReincarnationStats::MAX_STATS);

            if ($character->{$stat} < $floor) {
                $updates[$stat] = $floor;
            }
        }

        if (empty($updates)) {
            return;
        }

        $character->update($updates);
    }
}
