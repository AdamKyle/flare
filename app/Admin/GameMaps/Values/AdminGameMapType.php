<?php

namespace App\Admin\GameMaps\Values;

use App\Flare\GemWorldGeneration\Values\GeneratedGemMapType;
use App\Flare\Models\GameMap;

enum AdminGameMapType: string
{
    case BASE = 'base';
    case MAP_GEM_WORLD = 'map_gem_world';
    case LOCATION_GEM_WORLD = 'location_gem_world';

    /**
     * Resolve the Admin Game Map classification for a Game Map.
     *
     * @param GameMap $gameMap
     * @return AdminGameMapType
     */
    public static function fromGameMap(GameMap $gameMap): self
    {
        return match ($gameMap->generated_map_type) {
            GeneratedGemMapType::MAP_GEM->value => self::MAP_GEM_WORLD,
            GeneratedGemMapType::LOCATION_GEM->value => self::LOCATION_GEM_WORLD,
            default => self::BASE,
        };
    }

    /**
     * Resolve the display label for this classification.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::BASE => 'Base Map',
            self::MAP_GEM_WORLD => 'World Gem',
            self::LOCATION_GEM_WORLD => 'Location Gem',
        };
    }
}
