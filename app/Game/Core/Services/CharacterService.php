<?php

namespace App\Game\Core\Services;

use App;
use App\Flare\Models\Character;
use App\Game\Core\Values\LevelUpValue;

class CharacterService
{
    /**
     * Level up the charater.
     * 
     * @param Character $character
     * @return void
     */
    public function levelUpCharacter(Character $character): void
    {
        $character->update(resolve(LevelUpValue::class)->createValueObject($character));
    }
}
