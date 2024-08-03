<?php

namespace App\Game\Maps\Services;

use App\Flare\Cache\CoordinatesCache;
use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Game\Battle\Services\ConjureService;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Maps\Events\MoveTimeOutEvent;
use App\Game\Maps\Values\MapPositionValue;
use App\Game\Maps\Values\MapTileValue;

class SetSailService extends BaseMovementService
{
    use ResponseBuilder;

    private PortService $portService;

    public function __construct(MapTileValue $mapTileValue,
        MapPositionValue $mapPositionValue,
        CoordinatesCache $coordinatesCache,
        ConjureService $conjureService,
        MovementService $movementService,
        PortService $portService,
        TraverseService $traverseService,
    ) {
        parent::__construct($mapTileValue,
            $mapPositionValue,
            $coordinatesCache,
            $conjureService,
            $movementService,
            $traverseService,
        );

        $this->portService = $portService;
    }

    /**
     * Set sail to a new port from the current port.
     */
    public function setSail(Character $character): array
    {
        $toPort = $this->getToLocation($character);
        $fromPort = $this->getFromLocation($character);

        if (is_null($toPort) || is_null($fromPort)) {
            return $this->errorResult('Invalid location');
        }

        if ($this->cost > $character->gold) {
            return $this->errorResult('Not enough gold.');
        }

        if (! $this->portService->doesMatch($character, $fromPort, $toPort, $this->timeout, $this->cost)) {
            return $this->errorResult('Nice try. The details do not match.');
        }

        $character = $this->moveCharacterToNewPort($character, $toPort);

        if ($this->awakensCelestial()) {
            $this->conjureService->movementConjure($character);
        }

        $this->movementService->giveLocationReward($character, $toPort);

        if ($this->traversePlayer($toPort, $character)) {
            return $this->successResult($this->movementService->accessLocationService()->getLocationData($character));
        }

        $this->updateMonstersList($character, $toPort);

        $this->updateKingdomOwnedKingdom($character);

        return $this->successResult($this->movementService->accessLocationService()->getLocationData($character));
    }

    /**
     * Get the location to head towards.
     */
    protected function getToLocation(Character $character): ?Location
    {
        return Location::where('x', $this->x)
            ->where('y', $this->y)
            ->where('is_port', true)
            ->where('game_map_id', $character->map->game_map_id)
            ->first();
    }

    /**
     * Get the location you are coming from.
     */
    protected function getFromLocation(Character $character): ?Location
    {
        return Location::where('x', $character->map->character_position_x)
            ->where('y', $character->map->character_position_y)
            ->where('is_port', true)
            ->where('game_map_id', $character->map->game_map_id)
            ->first();
    }

    /**
     * Move the character to the new port.
     */
    protected function moveCharacterToNewPort(Character $character, Location $location): Character
    {
        $character = $this->portService->setSail($character, $location);

        $character = $character->refresh();

        event(new MoveTimeOutEvent($character, $this->timeout, true));

        event(new UpdateTopBarEvent($character));

        return $character;
    }
}
