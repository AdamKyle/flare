<?php

namespace App\Game\Core\Values;

use App\Flare\Models\Character;

class LevelUpValue {

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

        $baseStatMod       = $this->addModifier($character, 'base_stat_mod');
        $baseDamageStatMod = $this->addModifier($character, 'base_damage_stat_mod', true);

        return [
            'level'                => $character->level + 1,
            'xp'                   => $leftOverXP,
            'xp_next'              => 100,
            'str'                  => $this->addValue($character, 'str'),
            'dur'                  => $this->addValue($character, 'dur'),
            'dex'                  => $this->addValue($character, 'dex'),
            'chr'                  => $this->addValue($character, 'chr'),
            'int'                  => $this->addValue($character, 'int'),
            'agi'                  => $this->addValue($character, 'agi'),
            'focus'                => $this->addValue($character, 'focus'),
            'base_stat_mod'        => min($baseStatMod, 5.0),
            'base_damage_stat_mod' => min($baseDamageStatMod, 5.0),
        ];
    }

    /**
     * Add the new value to the character stat.
     *
     * Regular stats get +1 and the damage stat gets a +2
     *
     * @param Character $character
     * @param string $currenStat
     * @return int
     */
    protected function addValue(Character $character, string $currenStat): int {

        if ($character->{$currenStat} >= 999999) {
            return $character->{$currenStat};
        }

        if ($character->damage_stat === $currenStat) {
            return $character->{$currenStat} += 2;
        }

        return $character->{$currenStat} += 1;
    }

    /**
     * Add to the stat modifier pool when the stats are maxed out.
     *
     * @param Character $character
     * @param string $stat
     * @param bool $isDamage
     * @return float
     */
    protected function addModifier(Character $character, string $stat, bool $isDamage = false): float {

        if ($isDamage && $character->{$character->damage_stat} >= 999999) {
            return $character->{$stat} + 0.002;
        }

        if ($character->str >= 999999 && !$isDamage) {
            return $character->{$stat} + 0.0001;
        }

        return 0.0;
    }
}
