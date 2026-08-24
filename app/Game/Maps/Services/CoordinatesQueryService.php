<?php

namespace App\Game\Maps\Services;

use App\Game\Maps\Cache\CoordinatesCache;
use App\Game\Maps\Contracts\CoordinatesQuery;
use App\Game\Maps\Values\Coordinates;

class CoordinatesQueryService implements CoordinatesQuery
{
    public function __construct(
        private readonly CoordinatesCache $coordinatesCache,
    ) {}

    /**
     * Return the game world's cached X/Y coordinate grid.
     */
    public function get(): Coordinates
    {
        $coordinates = $this->coordinatesCache->getFromCache();

        return new Coordinates($coordinates['x'], $coordinates['y']);
    }
}
