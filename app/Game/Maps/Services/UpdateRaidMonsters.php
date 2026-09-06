<?php

namespace App\Game\Maps\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Game\Battle\Services\ConjureService;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Maps\Cache\CoordinatesCache;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Monsters\Services\MonsterListService;

class UpdateRaidMonsters extends BaseMovementService
{
    public function __construct(
        MapTileValue $mapTileValue,
        CoordinatesCache $coordinatesCache,
        ConjureService $conjureService,
        MovementService $movementService,
        TraverseService $traverseService,
        MonsterListService $monsterListService,
        ChanceCalculator $chanceCalculator,
    ) {
        parent::__construct(
            $mapTileValue,
            $coordinatesCache,
            $conjureService,
            $movementService,
            $traverseService,
            $monsterListService,
            $chanceCalculator,
        );
    }

    /**
     * Update the monster list for the raid at the given Location for the Character.
     */
    public function updateMonstersForRaidLocations(Character $character, Location $location): void
    {
        $this->updateMonstersList($character, $location);
    }
}
