<?php

namespace App\Flare\GemWorldGeneration\Values;

enum GeneratedGemMapType: string
{
    case MAP_GEM = 'map_gem';
    case LOCATION_GEM = 'location_gem';

    public function label(): string
    {
        return match ($this) {
            self::MAP_GEM => 'Map Gem',
            self::LOCATION_GEM => 'Location Gem',
        };
    }
}
