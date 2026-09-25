<?php

namespace App\Game\Core\Values;

use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\MaxLevelConfiguration;
use App\Game\Character\Concerns\Boons;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Reincarnate\Values\MaxReincarnationStats;

class LevelUpValue
{
    use Boons;

    const BASE_STAT_DAMAGE_MODIFIER = 'base_damage_stat_mod';

    const BASE_STAT_MODIFIER = 'base_stat_mod';

    /**
     * Build the level, XP, core stat, and modifier values for one level up trigger, resolving the Character's boon and max level rules.
     *
     * @param Character $character
     * @param int $leftOverXP
     * @return array
     */
    public function createValueObject(Character $character, int $leftOverXP = 0): array
    {
        $levelsToGain = $this->gainsAdditionalLevelOnLevelUp($character) ? $this->additionalLevelsToGain($character) : 1;

        return $this->createValueObjectForResolvedRules(
            $character,
            $leftOverXP,
            $this->getMaxLevel($character),
            $levelsToGain,
        );
    }

    /**
     * Build the level, XP, core stat, and modifier values for one level up trigger using an already resolved max level and levels per trigger.
     *
     * @param Character $character
     * @param int $leftOverXP
     * @param int $maxLevel
     * @param int $levelsToGain
     * @return array
     */
    public function createValueObjectForResolvedRules(
        Character $character,
        int $leftOverXP,
        int $maxLevel,
        int $levelsToGain,
    ): array {
        $newLevel = min($character->level + $levelsToGain, $maxLevel);
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
     * Add the gained levels to a core stat: the damage stat gains two per level and every other stat gains one, capped at the max stat value.
     *
     * @param Character $character
     * @param string $currenStat
     * @param int $levelsGained
     * @return int
     */
    private function addValue(Character $character, string $currenStat, int $levelsGained = 1): int
    {

        if ($character->damage_stat === $currenStat) {
            return min($character->{$currenStat} + ($levelsGained * 2), MaxReincarnationStats::MAX_STATS);
        }

        return min($character->{$currenStat} + $levelsGained, MaxReincarnationStats::MAX_STATS);
    }

    /**
     * Add to the stat modifier pool when the relevant stat is already maxed out.
     *
     * @param Character $character
     * @param string $stat
     * @param int $levelsGained
     * @return float
     */
    private function addModifier(Character $character, string $stat, int $levelsGained = 1): float
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
     * Return the Character's max level, which is raised by the continue leveling quest item.
     *
     * @param Character $character
     * @return int
     */
    private function getMaxLevel(Character $character): int
    {
        if ($this->canContinueLeveling($character)) {
            return MaxLevelConfiguration::first()->max_level;
        }

        return 1000;
    }

    /**
     * Determine whether the Character holds the continue leveling quest item.
     *
     * @param Character $character
     * @return bool
     */
    private function canContinueLeveling(Character $character): bool
    {
        $inventory = Inventory::where('character_id', $character->id)->first();

        return $inventory->slots->filter(function ($slot) {
            return $slot->item->effect === ItemEffectType::CONTINUE_LEVELING->value;
        })->isNotEmpty();
    }
}
