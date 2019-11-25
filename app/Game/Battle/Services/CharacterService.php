<?php

namespace App\Game\Battle\Services;

use App;
use App\Flare\Models\Character;
use App\Game\Battle\Values\LevelUpValue;

class CharacterService {

    public function levelUpCharacter(Character $character) {
        $character->update(resolve(LevelUpValue::class)->createValueObject($character));
    }
}
