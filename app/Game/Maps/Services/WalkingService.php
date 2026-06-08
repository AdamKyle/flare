<?php

namespace App\Game\Maps\Services;

use App\Flare\Cache\CoordinatesCache;
use App\Flare\Models\Character;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Battle\Services\ConjureService;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Maps\Events\MoveTimeOutEvent;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Messages\Events\ServerMessageEvent;
use Exception;

class WalkingService extends BaseMovementService
{
    use ResponseBuilder;

    public function __construct(
        MapTileValue $mapTileValue,
        CoordinatesCache $coordinatesCache,
        ConjureService $conjureService,
        MovementService $movementService,
        TraverseService $traverseService,
    ) {
        parent::__construct(
            $mapTileValue,
            $coordinatesCache,
            $conjureService,
            $movementService,
            $traverseService,
        );
    }

    /**
     * Move a character.
     *
     * @throws Exception
     */
    public function movePlayerToNewLocation(Character $character): array
    {
        $deltaX = abs($this->x - $character->map->character_position_x);
        $deltaY = abs($this->y - $character->map->character_position_y);

        if (! (($deltaX === 16 && $deltaY === 0) || ($deltaX === 0 && $deltaY === 16))) {
            return $this->errorResult('Invalid movement.');
        }

        $location = $this->getLocationForCoordinates($character);

        $restriction = $this->automationRestrictionErrorResult($character, AutomationRestrictionService::DIRECTIONAL_MOVEMENT, $location);

        if (! is_null($restriction)) {
            event(new ServerMessageEvent($character->user, $restriction['message']));

            return $restriction;
        }

        if (! $this->validateCoordinates()) {
            return $this->errorResult('You cannot go any further that way.');
        }

        $this->mapTileValue->setUp($character, $character->map->gameMap);

        if (! $this->mapTileValue->canWalk($this->x, $this->y)) {
            event(new ServerMessageEvent($character->user, 'You are missing a specific quest item for that.
            Click the map name under the map to see what item you need.'));

            return $this->errorResult('Missing item to do that.');
        }

        if (! is_null($location)) {
            if (! $this->canPlayerEnterLocation($character, $location)) {
                return $this->successResult();
            }
        }

        if ($this->awakensCelestial()) {
            $this->conjureService->movementConjure($character);
        }

        $character = $this->updateCharacterMapPosition($character);

        if (! is_null($location)) {

            if ($this->traversePlayer($location, $character)) {
                return $this->successResult($this->movementService->accessLocationService()->getCharacterPositionData($character->map));
            }

            $this->movementService->giveLocationReward($character, $location);
        }

        $this->updateMonstersList($character, $location);
        $this->updateKingdomOwnedKingdom($character);

        event(new MoveTimeOutEvent($character));

        $this->updateMonstersList($character);

        return $this->successResult($this->movementService->accessLocationService()->getCharacterPositionData($character->map));
    }
}
