<?php

namespace App\Admin\Services;

use App\Flare\Cache\CoordinatesCache;
use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Values\LocationType;

class LocationService
{
    private CoordinatesCache $coordinatesCache;

    public function __construct(CoordinatesCache $coordinatesCache)
    {
        $this->coordinatesCache = $coordinatesCache;
    }

    /**
     * Get view variables.
     */
    public function getViewVariables(?Location $location = null): array
    {
        return [
            'coordinates' => $this->coordinatesCache->getFromCache(),
            'gameMaps' => GameMap::pluck('name', 'id')->toArray(),
            'questItems' => Item::where('type', 'quest')->pluck('name', 'id')->toArray(),
            'locationTypes' => LocationType::getNamedValues(),
            'location' => $location,
            'specialCssPins' => $this->getLocationCssPins(),
        ];
    }

    protected function getLocationCssPins(): array
    {
        return [
            'christmas-tree-x-pin' => 'Christmas Tree',
            'snowman-x-pin' => 'Snowman',
        ];
    }
}
