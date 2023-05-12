<?php

namespace App\Game\Maps\Services;

use App\Flare\Models\Location;
use App\Flare\Models\Character;
use App\Flare\Cache\CoordinatesCache;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Maps\Values\MapPositionValue;
use App\Game\Battle\Services\ConjureService;
use App\Game\Maps\Services\BaseMovementService;

class UpdateRaidMonsters extends BaseMovementService {

    /**
     * @param MapTileValue $mapTileValue
     * @param MapPositionValue $mapPositionValue
     * @param CoordinatesCache $coordinatesCache
     * @param ConjureService $conjureService
     * @param MovementService $movementService
     */
    public function __construct(MapTileValue     $mapTileValue,
                                MapPositionValue $mapPositionValue,
                                CoordinatesCache $coordinatesCache,
                                ConjureService   $conjureService,
                                MovementService  $movementService
    ) {
        parent::__construct($mapTileValue,
            $mapPositionValue,
            $coordinatesCache,
            $conjureService,
            $movementService
        );
    }

    public function updateMonstersForRaidLocations(Character $character, Location $location): void {
        $this->updateMonstersList($character, $location);
    }

}