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
        $baseStatMod = $this->addModifier($character, self::BASE_STAT_MODIFIER, $gainsAdditionalLevel);
        $baseDamageStatMod = $this->addModifier($character, self::BASE_STAT_DAMAGE_MODIFIER, $gainsAdditionalLevel);
        $newLevel = $character->level + ($gainsAdditionalLevel ? $this->additionalLevelsToGain($character) : 1);
        $maxLevel = $this->getMaxLevel($character);

        if ($newLevel > $maxLevel) {
            $newLevel = $maxLevel;
        }

        return [
            'level' => $newLevel,
            'xp' => $newLevel === $maxLevel ? 0 : $leftOverXP,
            'xp_next' => 100,
            'str' => $this->addValue($character, 'str', $gainsAdditionalLevel),
            'dur' => $this->addValue($character, 'dur', $gainsAdditionalLevel),
            'dex' => $this->addValue($character, 'dex', $gainsAdditionalLevel),
            'chr' => $this->addValue($character, 'chr', $gainsAdditionalLevel),
            'int' => $this->addValue($character, 'int', $gainsAdditionalLevel),
            'agi' => $this->addValue($character, 'agi', $gainsAdditionalLevel),
            'focus' => $this->addValue($character, 'focus', $gainsAdditionalLevel),
            'base_stat_mod' => min($baseStatMod, 5.0),
            'base_damage_stat_mod' => min($baseDamageStatMod, 10.0),
        ];
    }

    /**
     * Add the new value to the character stat.
     *
     * Regular stats get +1 and the damage stat gets a +2
     */
    protected function addValue(Character $character, string $currenStat, bool $gainsAdditionalLevel = false): int
    {

        if ($character->{$currenStat} >= 999999) {
            return $character->{$currenStat};
        }

        if ($character->damage_stat === $currenStat) {
            return $character->{$currenStat} += ($gainsAdditionalLevel ? 4 : 2);
        }

        return $character->{$currenStat} += ($gainsAdditionalLevel ? 2 : 1);
    }

    /**
     * Add to the stat modifier pool when the stats are maxed out.
     */
    protected function addModifier(Character $character, string $stat, bool $gainAdditionalLevel = false): float
    {

        if ($character->{$character->damage_stat} >= MaxReincarnationStats::MAX_STATS && $stat === self::BASE_STAT_DAMAGE_MODIFIER) {
            $damageStatBonus = $character->{$stat} + ($gainAdditionalLevel ? 0.0002 : 0.0001);

            if ($damageStatBonus > 0.50) {
                return 0.50;
            }

            return $damageStatBonus;
        }


        if ($character->str >= MaxReincarnationStats::MAX_STATS && $stat === self::BASE_STAT_MODIFIER) {
            $baseStatBonus = $character->{$stat} + ($gainAdditionalLevel ? 0.00024 : 0.00012);

            if ($baseStatBonus > 0.60) {
                return 0.60;
            }

            return $baseStatBonus;
        }

        return 0.0;
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
