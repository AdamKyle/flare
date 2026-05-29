<?php

namespace App\Game\Core\Values;

use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\MaxLevelConfiguration;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Character\Concerns\Boons;
use App\Game\Reincarnate\Values\MaxReincarnationStats;

class LevelUpValue
{
    use Boons;

    const BASE_STAT_DAMAGE_MODIFIER = 'base_damage_stat_mod';

    const BASE_STAT_MODIFIER = 'base_stat_mod';

    /**
     * Create the level up value object.
     *
     * Increases core stats.
     */
    public function createValueObject(Character $character, int $leftOverXP = 0): array
    {

        $gainsAdditionalLevel = $this->gainsAdditionalLevelOnLevelUp($character);
        $newLevel = $character->level + ($gainsAdditionalLevel ? $this->additionalLevelsToGain($character) : 1);
        $maxLevel = $this->getMaxLevel($character);

        if ($newLevel > $maxLevel) {
            $newLevel = $maxLevel;
        }

        $levelsGained = $newLevel - $character->level;
        $baseStatMod = $this->addModifier($character, self::BASE_STAT_MODIFIER, $levelsGained);
        $baseDamageStatMod = $this->addModifier($character, self::BASE_STAT_DAMAGE_MODIFIER, $levelsGained);

        return [
            'level' => $newLevel,
            'xp' => $newLevel === $maxLevel ? 0 : $leftOverXP,
            'xp_next' => 100,
            'str' => $this->addValue($character, 'str', $levelsGained),
            'dur' => $this->addValue($character, 'dur', $levelsGained),
            'dex' => $this->addValue($character, 'dex', $levelsGained),
            'chr' => $this->addValue($character, 'chr', $levelsGained),
            'int' => $this->addValue($character, 'int', $levelsGained),
            'agi' => $this->addValue($character, 'agi', $levelsGained),
            'focus' => $this->addValue($character, 'focus', $levelsGained),
            'base_stat_mod' => min($baseStatMod, 0.60),
            'base_damage_stat_mod' => min($baseDamageStatMod, 0.50),
        ];
    }

    /**
     * Add the new value to the character stat.
     *
     * Regular stats get +1 and the damage stat gets a +2
     */
    protected function addValue(Character $character, string $currenStat, int $levelsGained = 1): int
    {

        if ($character->damage_stat === $currenStat) {
            return min($character->{$currenStat} + ($levelsGained * 2), MaxReincarnationStats::MAX_STATS);
        }

        return min($character->{$currenStat} + $levelsGained, MaxReincarnationStats::MAX_STATS);
    }

    /**
     * Add to the stat modifier pool when the stats are maxed out.
     */
    protected function addModifier(Character $character, string $stat, int $levelsGained = 1): float
    {

        if ($character->{$character->damage_stat} >= MaxReincarnationStats::MAX_STATS && $stat === self::BASE_STAT_DAMAGE_MODIFIER) {
            $damageStatBonus = $character->{$stat} + (0.0001 * $levelsGained);

            if ($damageStatBonus > 0.50) {
                return 0.50;
            }

            return $damageStatBonus;
        }

        if ($character->str >= MaxReincarnationStats::MAX_STATS && $stat === self::BASE_STAT_MODIFIER) {
            $baseStatBonus = $character->{$stat} + (0.00012 * $levelsGained);

            if ($baseStatBonus > 0.60) {
                return 0.60;
            }

            return $baseStatBonus;
        }

        return $character->{$stat};
    }

    /**
     * Get the max level for the character.
     */
    protected function getMaxLevel(Character $character): int
    {
        if ($this->canContinueLeveling($character)) {
            return MaxLevelConfiguration::first()->max_level;
        }

        return 1000;
    }

    /**
     * Can we continue to level?
     */
    protected function canContinueLeveling(Character $character): bool
    {
        $inventory = Inventory::where('character_id', $character->id)->first();

        return $inventory->slots->filter(function ($slot) {
            return $slot->item->effect === ItemEffectsValue::CONTINUE_LEVELING;
        })->isNotEmpty();
    }
}
