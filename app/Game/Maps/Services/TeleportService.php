<?php

namespace App\Game\Maps\Services;

use App\Flare\Cache\CoordinatesCache;
use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Battle\Services\ConjureService;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Maps\Events\MoveTimeOutEvent;
use App\Game\Maps\Values\MapTileValue;
use Exception;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class TeleportService extends BaseMovementService
{
    use ResponseBuilder;

    private bool $characterTraversed = false;

    public function __construct(
        MapTileValue $mapTileValue,
        CoordinatesCache $coordinatesCache,
        ConjureService $conjureService,
        MovementService $movementService,
        TraverseService $traverseService,
        private readonly Manager $manager,
        private readonly CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer,
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

        $character = $this->teleportCharacter($character, $location, $usingPCTCommand);

        return $this->successResult(
            [
                'character_position_data' => $this->movementService->accessLocationService()->getCharacterPositionData($character->map),
                'has_traversed' => $this->characterTraversed,
            ]
        );
    }

    /**
     * Get weather the character has traversed or not.
     */
    public function getHasCharacterTraversed(): bool
    {
        return $this->characterTraversed;
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
    protected function teleportCharacter(Character $character, ?Location $location = null, bool $pctCommand = false): Character
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

        $characterData = new Item($character, $this->characterSheetBaseInfoTransformer);
        $characterData = $this->manager->createData($characterData)->toArray();

        event(new UpdateBaseCharacterInformation($character->user, $characterData));

        if (! is_null($location)) {

            if ($this->traversePlayer($location, $character)) {

                $this->characterTraversed = true;

                return $character;
            }

            $this->movementService->giveLocationReward($character, $location);
        }

        return $character;
    }
}
