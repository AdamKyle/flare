<?php

namespace App\Game\Character\Concerns;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterBoon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

trait Boons
{
    public function fetchCharacterBoons(Character $character): Collection
    {
        return CharacterBoon::active()->where('character_id', $character->id)->with('itemUsed')->get();
    }

    public function gainsAdditionalLevelOnLevelUp(Character $character): bool
    {
        return CharacterBoon::active()->where('character_id', $character->id)->join('items', function ($join) {
            $join->on('items.id', '=', 'character_boons.item_id');
        })->where('items.gains_additional_level', true)->get()->isNotEmpty();
    }

    public function additionalLevelsToGain(Character $character): int
    {
        return CharacterBoon::active()->where('character_id', $character->id)
            ->join('items', function ($join) {
                $join->on('items.id', '=', 'character_boons.item_id');
            })
            ->where('items.gains_additional_level', true)
            ->sum('character_boons.amount_used') + 1;
    }

    public function fetchXpBonus(Character $character): float
    {
        return (float) CharacterBoon::active()->where('character_id', $character->id)->join('items', function ($join) {
            $join->on('items.id', '=', 'character_boons.item_id');
        })->sum(DB::raw('COALESCE(items.xp_bonus, 0) * CASE WHEN items.can_stack = 1 THEN character_boons.amount_used ELSE 1 END'));
    }

    public function fetchFightTimeOutModifier(Character $character): float
    {
        return (float) CharacterBoon::active()->where('character_id', $character->id)->join('items', function ($join) {
            $join->on('items.id', '=', 'character_boons.item_id');
        })->sum(DB::raw('COALESCE(items.fight_time_out_mod_bonus, 0) * CASE WHEN items.can_stack = 1 THEN character_boons.amount_used ELSE 1 END'));
    }

    public function fetchMoveTimOutModifier(Character $character): float
    {
        return (float) CharacterBoon::active()->where('character_id', $character->id)->join('items', function ($join) {
            $join->on('items.id', '=', 'character_boons.item_id');
        })->sum(DB::raw('COALESCE(items.move_time_out_mod_bonus, 0) * CASE WHEN items.can_stack = 1 THEN character_boons.amount_used ELSE 1 END'));
    }
}
