<?php

namespace App\Game\Maps\Services\Common;

use App\Flare\Models\Character;

trait LiveCharacterCount
{
    /**
     * Returns a character count of characters on this map.
     */
    public function getActiveUsersCountForMap(Character $character): int
    {
        return Character::join('maps', function ($query) use ($character) {
            $mapId = $character->map->game_map_id;
            $query->on('characters.id', 'maps.character_id')->where('game_map_id', $mapId);
        })->join('sessions', function ($join) {
            $join->on('sessions.user_id', 'characters.user_id')
                ->where('last_activity', '<', now()->addMinutes(5)->timestamp);
        })->count();
    }
}
