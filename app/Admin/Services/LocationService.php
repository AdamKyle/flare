<?php

namespace App\Admin\Services;

use App\Flare\Cache\CoordinatesCache;
use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Values\LocationEffectValue;
use App\Flare\Values\LocationType;

class LocationService {

    private CoordinatesCache $coordinatesCache;

    /**
     * @param CoordinatesCache $coordinatesCache
     */
    public function __construct(CoordinatesCache $coordinatesCache) {
        $this->coordinatesCache = $coordinatesCache;
    }

    /**
     * Get view variables.
     *
     * @return array
     */
    public function getViewVariables(Location $location = null): array {
        return [
            'coordinates'     => $this->coordinatesCache->getFromCache(),
            'gameMaps'        => GameMap::pluck('name', 'id')->toArray(),
            'locationEffects' => LocationEffectValue::getNamedValues(),
            'questItems'      => Item::where('type', 'quest')->pluck('name', 'id')->toArray(),
            'locationTypes'   => LocationType::getNamedValues(),
            'location'        => $location,
         ];
    }
}
