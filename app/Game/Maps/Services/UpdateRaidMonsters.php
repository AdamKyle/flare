<?php

namespace App\Game\Maps\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Game\Battle\Services\ConjureService;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Maps\Cache\CoordinatesCache;
use App\Game\Maps\Values\MapTileValue;

class UpdateRaidMonsters extends BaseMovementService
{
    /**
     * @param MapTileValue $mapTileValue
     * @param CoordinatesCache $coordinatesCache
     * @param ConjureService $conjureService
     * @param MovementService $movementService
     * @param TraverseService $traverseService
     * @param ChanceCalculator $chanceCalculator
     */
    public function __construct(
        MapTileValue $mapTileValue,
        CoordinatesCache $coordinatesCache,
        ConjureService $conjureService,
        MovementService $movementService,
        TraverseService $traverseService,
        ChanceCalculator $chanceCalculator,
    ) {
        parent::__construct(
            $mapTileValue,
            $coordinatesCache,
            $conjureService,
            $movementService,
            $traverseService,
            $chanceCalculator,
        );
    }

    public function updateMonstersForRaidLocations(Character $character, Location $location): void
    {
        $this->updateMonstersList($character, $location);
    }
}
