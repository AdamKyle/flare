<?php

namespace App\Game\Core\Values;

use App\Flare\Models\Character;

class LevelUpValue {


    public function createValueObject(Character $character) {
        return [
            'level' => $character->level + 1,
            'xp'    => 0,
            'str'   => $this->addValue($character, 'str'),
            'dur'   => $this->addValue($character, 'dur'),
            'dex'   => $this->addValue($character, 'dex'),
            'chr'   => $this->addValue($character, 'chr'),
            'int'   => $this->addvalue($character, 'int'),
        ];
    }

    private function addValue(Character $character, string $currenStat): int {
        if ($character->damage_stat === $currenStat) {
            return $character->{$currenStat} += 2;
        }

        return $character->{$currenStat} += 1;
    }
}
