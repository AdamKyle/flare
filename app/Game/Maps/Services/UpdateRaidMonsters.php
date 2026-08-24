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
