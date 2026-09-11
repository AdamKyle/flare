<?php

namespace App\Game\Gems\Progression\Services;

use App\Flare\GemWorldGeneration\Values\GeneratedGemMapType;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Game\Gems\Progression\Values\ResolvedGemWorldProfile;

/**
 * Resolves which exact Map/Location Gem profile owns a generated Gem World
 * or a Character's current generated-world context. This is the single
 * shared entry point for that resolution; callers must never accept a Gem
 * profile id from client input.
 */
class GemWorldProfileResolver
{
    /**
     * Resolve the owning Gem profile for the given Game Map, or null when
     * the Game Map is not a generated Gem World with a resolvable profile.
     */
    public function resolveForGameMap(GameMap $gameMap): ?ResolvedGemWorldProfile
    {
        if (! $gameMap->isGeneratedGemMap()) {
            return null;
        }

        $generatedMapType = GeneratedGemMapType::tryFrom($gameMap->generated_map_type);

        if ($generatedMapType === GeneratedGemMapType::MAP_GEM) {
            $profile = $gameMap->generatedMapGemParamter;

            return is_null($profile) ? null : ResolvedGemWorldProfile::forMapProfile($profile, $gameMap);
        }

        if ($generatedMapType === GeneratedGemMapType::LOCATION_GEM) {
            $profile = $gameMap->generatedLocationGemParamter;

            return is_null($profile) ? null : ResolvedGemWorldProfile::forLocationProfile($profile, $gameMap);
        }

        return null;
    }

    /**
     * Resolve the owning Gem profile for the Character's current Game Map,
     * or null when the Character is not currently inside a generated Gem
     * World with a resolvable profile.
     */
    public function resolveForCharacter(Character $character): ?ResolvedGemWorldProfile
    {
        $map = $character->map;

        if (is_null($map) || is_null($map->gameMap)) {
            return null;
        }

        return $this->resolveForGameMap($map->gameMap);
    }
}
