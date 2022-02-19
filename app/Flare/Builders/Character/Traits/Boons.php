<?php

namespace App\Flare\Builders\Character\Traits;

use Illuminate\Database\Eloquent\Collection;
use App\Flare\Values\ItemUsabilityType;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterBoon;

trait Boons {

    /**
     * Fetches current boons on a character.
     *
     * @param Character $character
     * @return Collection
     */
    public function fetchCharacterBoons(Character $character): Collection{
        return CharacterBoon::where('character_id', $character->id)->get();
    }

    public function fetchStatIncrease(Character $character, string $statAttribute): float {
        return CharacterBoon::where('character_id', $character->id)->whereNotNull($statAttribute)->sum($statAttribute);
    }

    public function fetchStatIncreaseFromType(Character $character): float {
        return CharacterBoon::where('character_id', $character->id)->where('type', ItemUsabilityType::STAT_INCREASE)->sum('stat_bonus');
    }
}
