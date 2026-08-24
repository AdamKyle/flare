<?php

namespace App\Admin\Services;

use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Game\Maps\Contracts\CoordinatesQuery;
use App\Game\Maps\Values\LocationType;

class LocationService
{
    public function __construct(private readonly CoordinatesQuery $coordinatesQuery) {}

    /**
     * Get view variables.
     */
    public function getViewVariables(?Location $location = null): array
    {
        $coordinates = $this->coordinatesQuery->get();

        return [
            'coordinates' => [
                'x' => $coordinates->x,
                'y' => $coordinates->y,
            ],
            'gameMaps' => GameMap::pluck('name', 'id')->toArray(),
            'questItems' => Item::where('type', 'quest')->pluck('name', 'id')->toArray(),
            'locationTypes' => LocationType::getNamedValues(),
            'location' => $location,
            'specialCssPins' => $this->getLocationCssPins(),
        ];
    }

    /**
     * Return the map-pin CSS classes used by special seasonal locations.
     */
    protected function getLocationCssPins(): array
    {
        return [
            'christmas-tree-x-pin' => 'Christmas Tree',
            'snowman-x-pin' => 'Snowman',
        ];
    }
}
