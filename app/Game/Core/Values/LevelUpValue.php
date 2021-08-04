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
    public function createValueObject(Character $character) {
        return [
            'level' => $character->level + 1,
            'xp'    => 0,
            'str'   => $this->addValue($character, 'str'),
            'dur'   => $this->addValue($character, 'dur'),
            'dex'   => $this->addValue($character, 'dex'),
            'chr'   => $this->addValue($character, 'chr'),
            'int'   => $this->addvalue($character, 'int'),
            'agi'   => $this->addvalue($character, 'agi'),
            'focus' => $this->addvalue($character, 'focus'),
        ];
    }

    /**
     * Add the new value to the character stat.
     *
     * Regular stats get +1 and the damage stat gets a +2
     *
     * @param Character $character
     * @param string $currentStat
     * @return int
     */
    private function addValue(Character $character, string $currenStat): int {
        if ($character->damage_stat === $currenStat) {
            return $character->{$currenStat} += 2;
        }

        return $character->{$currenStat} += 1;
    }
}
