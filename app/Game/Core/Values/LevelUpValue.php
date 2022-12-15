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
     * @return array
     */
    public function createValueObject(Character $character, int $leftOverXP = 0) {
        return [
            'level'   => $character->level + 1,
            'xp'      => $leftOverXP,
            'xp_next' => 100,
            'str'     => $this->addValue($character, 'str'),
            'dur'     => $this->addValue($character, 'dur'),
            'dex'     => $this->addValue($character, 'dex'),
            'chr'     => $this->addValue($character, 'chr'),
            'int'     => $this->addValue($character, 'int'),
            'agi'     => $this->addValue($character, 'agi'),
            'focus'   => $this->addValue($character, 'focus'),
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
}
