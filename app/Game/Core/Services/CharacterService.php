<?php

namespace App\Game\Core\Services;

use App;
use App\Flare\Models\Character;
use App\Game\Core\Values\LevelUpValue;

class CharacterService
{
    public function levelUpCharacter(Character $character)
    {
        $character->update(resolve(LevelUpValue::class)->createValueObject($character));
    }
}
