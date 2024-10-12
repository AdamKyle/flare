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
use Exception;

class TeleportService extends BaseMovementService
{
    use ResponseBuilder;

    public function __construct(
        MapTileValue $mapTileValue,
        MapPositionValue $mapPositionValue,
        CoordinatesCache $coordinatesCache,
        ConjureService $conjureService,
        MovementService $movementService,
        TraverseService $traverseService,
    ) {
        parent::__construct(
            $mapTileValue,
            $mapPositionValue,
            $coordinatesCache,
            $conjureService,
            $movementService,
            $traverseService,
        );
    }

    /**
     * Teleport the character.
     *
     * @throws Exception
     */
    public function teleport(Character $character, bool $usingPCTCommand = false): array
    {
        if (! $this->canPlayerMoveToLocation($character)) {
            $this->generateCannotWalkServerMessage($character);

            return $this->errorResult('Cannot move there. Check server messages for reason.');
        }

        $location = $this->getLocationForCoordinates($character);

        if (! is_null($location)) {
            if (! $this->canPlayerEnterLocation($character, $location)) {
                return $this->errorResult('Cannot move there. Check server messages for reason.');
            }
        }

        if ($this->cost > $character->gold) {
            return $this->errorResult('Not enough gold to teleport to desired location.');
        }

        if (! $this->validateCoordinates()) {
            return $this->errorResult('Invalid coordinates');
        }

        if ($this->awakensCelestial()) {
            $this->conjureService->movementConjure($character);
        }

        $character = $this->updateCharacterMapPosition($character);

        $this->teleportCharacter($character, $location, $usingPCTCommand);

        return $this->successResult($this->movementService->accessLocationService()->getLocationData($character));
    }

    /**
     * Teleport the player.
     *
     * - If they used the /pct chat command they will not be charged and suffer
     *   no movement timeout.
     *
     * - If they did not use the /pct chat command they are charged and suffer a timeout
     *   in minutes.
     *
     * @throws Exception
     */
    protected function teleportCharacter(Character $character, ?Location $location = null, bool $pctCommand = false): void
    {

        $timeout = $this->timeout;
        $cost = $this->cost;

        if ($pctCommand) {
            $timeout = 0;
            $cost = 0;
        }

        $character->update([
            'can_move' => $timeout === 0 ? true : false,
            'gold' => $character->gold - $cost,
            'can_move_again_at' => $timeout === 0 ? null : now()->addMinutes($timeout),
        ]);

        $character = $character->refresh();

        if ($timeout !== 0) {
            event(new MoveTimeOutEvent($character, $timeout, true));
        }

        event(new UpdateTopBarEvent($character));

        if (! is_null($location)) {

            if ($this->traversePlayer($location, $character)) {
                return;
            }

            $this->movementService->giveLocationReward($character, $location);
        }

        $this->updateMonstersList($character, $location);
    }
}
