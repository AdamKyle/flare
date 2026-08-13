<?php

namespace App\Game\Maps\Services;

use App\Flare\Cache\CoordinatesCache;
use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Battle\Services\ConjureService;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Maps\Events\MoveTimeOutEvent;
use App\Game\Maps\Values\MapTileValue;

class SetSailService extends BaseMovementService
{
    use ResponseBuilder;

    private PortService $portService;

    public function __construct(
        MapTileValue $mapTileValue,
        CoordinatesCache $coordinatesCache,
        ConjureService $conjureService,
        MovementService $movementService,
        PortService $portService,
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

        $this->portService = $portService;
    }

    /**
     * Set sail to a new port from the current port.
     */
    public function setSail(Character $character): array
    {
        $restriction = $this->automationRestrictionErrorResult($character, AutomationRestrictionService::SET_SAIL);

        if (! is_null($restriction)) {
            return $restriction;
        }

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

        $character = $this->moveCharacterToNewPort($character);

        if ($this->awakensCelestial()) {
            $this->conjureService->movementConjure($character);
        }

        $this->movementService->giveLocationReward($character, $toPort);

        $hasTraversed = $this->traversePlayer($toPort, $character);

        if (! $hasTraversed) {
            $this->updateMonstersList($character, $toPort);
        }

        return $this->successResult([
            'character_position_data' => $this->movementService->accessLocationService()->getCharacterPositionData($character->map),
            'has_traversed' => $hasTraversed,
        ]);
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

    protected function moveCharacterToNewPort(Character $character): Character
    {
        $character = $this->updateCharacterMapPosition($character);

        $character->update([
            'gold' => $character->gold - $this->cost,
        ]);

        $character = $character->refresh();

        event(new MoveTimeOutEvent($character, $this->timeout, true));

        $character = $character->refresh();

        event(new UpdateCharacterBaseDetailsEvent($character));

        return $character;
    }
}
