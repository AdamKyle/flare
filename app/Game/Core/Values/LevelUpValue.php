<?php

namespace App\Game\Core\Values;

use App\Flare\Builders\Character\Traits\Boons;
use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\MaxLevelConfiguration;
use App\Flare\Values\ItemEffectsValue;

class LevelUpValue {

    use Boons;

    /**
     * Create the level up value object.
     *
     * Increases core stats.
     *
     * @param Character $character
     * @param int $leftOverXP
     * @return array
     */
    public function createValueObject(Character $character, int $leftOverXP = 0): array {

        $gainsAdditionalLevel = $this->gainsAdditionalLevelOnLevelUp($character);
        $baseStatMod          = $this->addModifier($character, 'base_stat_mod', false, $gainsAdditionalLevel);
        $baseDamageStatMod    = $this->addModifier($character, 'base_damage_stat_mod', true, $gainsAdditionalLevel);
        $newLevel             = $character->level + ($gainsAdditionalLevel ? 2 : 1);
        $maxLevel             = $this->getMaxLevel($character);

        if ($newLevel > $maxLevel) {
            $newLevel = $maxLevel;
        }

        return [
            'level'                => $newLevel,
            'xp'                   => $newLevel === $maxLevel ? 0 : $leftOverXP,
            'xp_next'              => 100,
            'str'                  => $this->addValue($character, 'str', $gainsAdditionalLevel),
            'dur'                  => $this->addValue($character, 'dur', $gainsAdditionalLevel),
            'dex'                  => $this->addValue($character, 'dex', $gainsAdditionalLevel),
            'chr'                  => $this->addValue($character, 'chr', $gainsAdditionalLevel),
            'int'                  => $this->addValue($character, 'int', $gainsAdditionalLevel),
            'agi'                  => $this->addValue($character, 'agi', $gainsAdditionalLevel),
            'focus'                => $this->addValue($character, 'focus', $gainsAdditionalLevel),
            'base_stat_mod'        => min($baseStatMod, 5.0),
            'base_damage_stat_mod' => min($baseDamageStatMod, 10.0),
        ];
    }

    /**
     * Add the new value to the character stat.
     *
     * Regular stats get +1 and the damage stat gets a +2
     *
     * @param Character $character
     * @param string $currenStat
     * @param bool $gainsAdditionalLevel
     * @return int
     */
    protected function addValue(Character $character, string $currenStat, bool $gainsAdditionalLevel = false): int {

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
     *
     * @param Character $character
     * @param string $stat
     * @param bool $isDamage
     * @param bool $gainAdditionalLevel
     * @return float
     */
    protected function addModifier(Character $character, string $stat, bool $isDamage = false, bool $gainAdditionalLevel = false): float {

        if ($isDamage && $character->{$character->damage_stat} >= 999999) {
            return $character->{$stat} + ($gainAdditionalLevel ? 0.0004 : 0.0002);
        }

        if ($character->str >= 999999 && !$isDamage) {
            return $character->{$stat} + ($gainAdditionalLevel ? 0.0002 : 0.0001);
        }

        return 0.0;
    }

    /**
     * Get the max level for the character.
     *
     * @param Character $character
     * @return int
     */
    protected function getMaxLevel(Character $character): int {
        if ($this->canContinueLeveling($character)) {
            return MaxLevelConfiguration::first()->max_level;
        }

        return 1000;
    }

    /**
     * Can we continue to level?
     *
     * @param Character $character
     * @return bool
     */
    protected function canContinueLeveling(Character $character): bool {
        $inventory = Inventory::where('character_id', $character->id)->first();

        return $inventory->slots->filter(function($slot) {
            return $slot->item->effect === ItemEffectsValue::CONTINUE_LEVELING;
        })->isNotEmpty();
    }
}
