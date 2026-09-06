<?php

namespace App\Game\Gems\Values;

/**
 * The closed set of Map/Location Gem gameplay contexts a resolved Area Gem
 * effect result can represent.
 */
enum AreaGemContext: string
{
    case MAP = 'map';
    case LOCATION = 'location';
    case MAP_GEM_WORLD = 'map_gem_world';
    case LOCATION_GEM_WORLD = 'location_gem_world';
}
