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
        return CharacterBoon::where('character_id', $character->id)->with('item')->get();
    }


    public function fetchStatIncrease(Character $character, string $statAttribute): float {
        return CharacterBoon::where('character_id', $character->id)->join('items', function($join) use ($statAttribute) {
            $join->on('items.id', '=', 'character_boons.item_id')
                 ->whereNotNull('items.' . $statAttribute);
        })->sum('items.' . $statAttribute);
    }

    public function fetchStatIncreaseSum(Character $character): float {
        return CharacterBoon::where('character_id', $character->id)->join('items', function($join) {
            $join->on('items.id', '=', 'character_boons.item_id');
        })->sum('items.stat_increase');
    }

    public function fetchFightTimeOutModifier(Character $character): float {
        return CharacterBoon::where('character_id', $character->id)->join('items', function($join) {
            $join->on('items.id', '=', 'character_boons.item_id');
        })->sum('items.fight_time_out_mod_bonus');
    }

    public function fetchMoveTimOutModifier(Character $character): float {
        return CharacterBoon::where('character_id', $character->id)->join('items', function($join) {
            $join->on('items.id', '=', 'character_boons.item_id');
        })->sum('items.move_time_out_mod_bonus');
    }
}
