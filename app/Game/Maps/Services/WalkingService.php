<?php

namespace App\Game\Maps\Services;

use App\Flare\Cache\CoordinatesCache;
use App\Flare\Models\Character;
use App\Game\Battle\Services\ConjureService;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Maps\Events\MoveTimeOutEvent;
use App\Game\Maps\Values\MapPositionValue;
use App\Game\Maps\Values\MapTileValue;

class WalkingService extends BaseMovementService
{

    use ResponseBuilder;

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

    /**
     * Move a character.
     *
     * @param Character $character
     * @return array
     */
    public function movePlayerToNewLocation(Character $character): array {

        if (!$this->validateCoordinates()) {
            return $this->errorResult('Invalid coordinates');
        }

        $location = $this->getLocationForCoordinates($character);

        if (!is_null($location)) {
            if (!$this->canPlayerEnterLocation($character, $location)) {
                return $this->successResult();
            }
        }

        if ($this->awakensCelestial()) {
            $this->conjureService->movementConjure($character);
        }

        $character = $this->updateCharacterMapPosition($character);

        $this->movementService->giveLocationReward($character, [
            'character_position_x' => $character->map->character_x_position,
            'character_position_y' => $character->map->character_y_position
        ]);

        event(new MoveTimeOutEvent($character));

        return $this->successResult($this->movementService->accessLocationService()->getLocationData($character));
    }
}
