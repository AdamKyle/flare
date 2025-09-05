<?php

namespace App\Game\Character\CharacterCreation\Pipeline\Steps;

use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use Closure;

class MapPlacement
{
    /**
     * Create the character's map row for the chosen GameMap.
     *
     * @param CharacterBuildState $state
     * @param Closure $next
     * @return CharacterBuildState
     */
    public function process(CharacterBuildState $state, Closure $next): CharacterBuildState
    {
        $character = $state->getCharacter();
        $map = $state->getMap();

        if ($character === null || $map === null) {
            return $next($state);
        }

        $character->map()->create([
            'character_id' => $character->id,
            'game_map_id' => $map->id,
        ]);

        return $next($state);
    }
}
