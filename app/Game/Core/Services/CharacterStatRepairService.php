<?php

namespace App\Game\Core\Services;

use App\Flare\Models\Character;
use App\Flare\Values\BaseStatValue;
use App\Game\Reincarnate\Values\MaxReincarnationStats;

class CharacterStatRepairService
{
    public function __construct(private readonly BaseStatValue $baseStatValue) {}

    public function getMinimumReincarnationBonus(Character $character, int $maxLevel): int
    {
        $bonus = 0;

        for ($reincarnationCount = 0; $reincarnationCount < $character->times_reincarnated; $reincarnationCount++) {
            $stat = 10 + $bonus + ($maxLevel - 1);
            $bonus += (int) floor($stat * 0.05);
            $bonus = min($bonus, MaxReincarnationStats::MAX_STATS);
        }

        return $bonus;
    }

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

    public function repair(Character $character): void
    {
        $baseStats = ['str', 'dur', 'dex', 'chr', 'int', 'agi', 'focus'];
        $levelUps = max($character->level - 1, 0);
        $reincarnatedStatIncrease = $character->reincarnated_stat_increase;
        $updates = [];
        $baseStatValue = $this->baseStatValue->setRace($character->race)->setClass($character->class);

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
