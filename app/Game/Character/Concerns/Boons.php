<?php

namespace App\Game\Character\Concerns;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterBoon;
use Illuminate\Database\Eloquent\Collection;

trait Boons
{
    /**
     * Fetches current boons on a character.
     */
    public function fetchCharacterBoons(Character $character): Collection
    {
        return CharacterBoon::where('character_id', $character->id)->with('itemUsed')->get();
    }

    /**
     * Do we have at least one item that lets the character gain an additional level on level up?
     */
    public function gainsAdditionalLevelOnLevelUp(Character $character): bool
    {
        return CharacterBoon::where('character_id', $character->id)->join('items', function ($join) {
            $join->on('items.id', '=', 'character_boons.item_id');
        })->where('items.gains_additional_level', true)->get()->isNotEmpty();
    }

    /**
     * How many additional levels do we get?
     */
    public function additionalLevelsToGain(Character $character): int
    {
        return CharacterBoon::where('character_id', $character->id)
            ->join('items', function ($join) {
                $join->on('items.id', '=', 'character_boons.item_id');
            })
            ->where('items.gains_additional_level', true)
            ->sum('character_boons.amount_used') + 1;
    }

    /**
     * Fetch Fight timeout modifier from boons.
     */
    public function fetchXpBonus(Character $character): float
    {
        return CharacterBoon::where('character_id', $character->id)->join('items', function ($join) {
            $join->on('items.id', '=', 'character_boons.item_id');
        })->sum('items.xp_bonus');
    }

    /**
     * Fetch Fight timeout modifier from boons.
     */
    public function fetchFightTimeOutModifier(Character $character): float
    {
        return CharacterBoon::where('character_id', $character->id)->join('items', function ($join) {
            $join->on('items.id', '=', 'character_boons.item_id');
        })->sum('items.fight_time_out_mod_bonus');
    }

    /**
     * Fetch the move time out modifer from boons.
     */
    public function fetchMoveTimOutModifier(Character $character): float
    {
        return CharacterBoon::where('character_id', $character->id)->join('items', function ($join) {
            $join->on('items.id', '=', 'character_boons.item_id');
        })->sum('items.move_time_out_mod_bonus');
    }
}
