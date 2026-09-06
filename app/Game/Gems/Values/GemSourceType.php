<?php

namespace App\Game\Gems\Values;

/**
 * The closed set of Gem profile sources that can contribute to a resolved
 * Area Gem effect result.
 */
enum GemSourceType: string
{
    case MAP_GEM = 'map_gem';
    case LOCATION_GEM = 'location_gem';
}
