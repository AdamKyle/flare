<?php

namespace App\Game\Maps\Services;

use App\Flare\Models\GameMap;
use App\Flare\Values\AutomationType;
use App\Game\Maps\Events\UpdateMonsterList;
use Illuminate\Support\Facades\Cache;
use App\Flare\Cache\CoordinatesCache;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\User;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Battle\Services\ConjureService;
use App\Game\Maps\Values\MapPositionValue;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Messages\Events\ServerMessageEvent;
use Facades\App\Flare\RandomNumber\RandomNumberGenerator;

class BaseMovementService {

    /**
     * @var MapTileValue $mapTileValue
     */
    protected MapTileValue $mapTileValue;

    /**
     * @var MapPositionValue $mapPositionValue
     */
    protected MapPositionValue $mapPositionValue;

    /**
     * @var CoordinatesCache $coordinatesCache
     */
    protected CoordinatesCache $coordinatesCache;

    /**
     * @var ConjureService $conjureService
     */
    protected ConjureService $conjureService;

    /**
     * @var MovementService $movementService
     */
    protected MovementService $movementService;

    /**
     * @param MapTileValue $mapTileValue
     * @param MapPositionValue $mapPositionValue
     * @param CoordinatesCache $coordinatesCache
     * @param ConjureService $conjureService
     * @param MovementService $movementService
     */
    public function __construct(MapTileValue $mapTileValue,
                                MapPositionValue $mapPositionValue,
                                CoordinatesCache $coordinatesCache,
                                ConjureService $conjureService,
                                MovementService $movementService) {

        $this->mapTileValue     = $mapTileValue;
        $this->mapPositionValue = $mapPositionValue;
        $this->coordinatesCache = $coordinatesCache;
        $this->conjureService   = $conjureService;
        $this->movementService  = $movementService;
    }

    /**
     * @var int $x
     */
    protected int $x;

    /**
     * @var int $y
     */
    protected int $y;

    /**
     * @var int $cost
     */
    protected int $cost;

    /**
     * @var int $timeout
     */
    protected int $timeout;

    /**
     * Set the coordinates to travel to.
     *
     * @param int $x
     * @param int $y
     * @return BaseMovementService
     */
    public function setCoordinatesToTravelTo(int $x, int $y): BaseMovementService {
        $this->x = $x;
        $this->y = $y;

        return $this;
    }

    /**
     * Set the cost of the teleport.
     *
     * @param int $cost
     * @return BaseMovementService
     */
    public function setCost(int $cost): BaseMovementService {
        $this->cost = $cost;

        return $this;
    }

    /**
     * Set the timeout value.
     *
     * @param int $timeout
     * @return BaseMovementService
     */
    public function setTimeOutValue(int $timeout): BaseMovementService {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Updates the map position of the character.
     *
     * @param Character $character
     * @return Character
     */
    protected function updateCharacterMapPosition(Character $character): Character {
        $map = $character->map;

        $map->update([
            'character_position_x' => $this->x,
            'character_position_y' => $this->y,
            'position_x'           => $this->mapPositionValue->fetchXPosition($this->x, $map->position_x),
            'position_y'           => $this->mapPositionValue->fetchYPosition($this->y),
        ]);

        return $character->refresh();
    }

    /**
     * Do we awaken a celestial when we move?
     *
     * @return bool
     */
    protected function awakensCelestial(): bool {
        if (Cache::has('celestial-spawn-rate')) {
            $needed = 100 - (100 * Cache::get('celestial-spawn-rate'));

            return rand(1, 100) > $needed;
        }

        return RandomNumberGenerator::generateRandomNumber(1, 50, 1, 100) > 99;
    }

    /**
     * Validates the coordinates the player want's to move too.
     *
     * @return bool
     */
    protected function validateCoordinates(): bool {
        $coordinates = $this->coordinatesCache->getFromCache();

        if (!in_array($this->x, $coordinates['x']) && !in_array($this->y, $coordinates['y'])) {
            return false;
        }

        return true;
    }

    /**
     * Returns the location at the coordinates the player wants to move too.
     *
     * - Location can be null.
     *
     * @param Character $character
     * @return Location|null
     */
    protected function getLocationForCoordinates(Character $character): ?Location {
        $gameMapId = $character->map->game_map_id;

        return Location::where('x', $this->x)->where('y', $this->y)->where('game_map_id', $gameMapId)->first();
    }

    /**
     * Can the player enter this location?
     *
     * @param Character $character
     * @param Location $location
     * @return bool
     */
    protected function canPlayerEnterLocation(Character $character, Location $location): bool {
        if (!$location->can_players_enter) {
            event(new ServerMessageEvent($character->user, 'You cannot enter this location. This is the PVP arena that is only open once per month.'));

            return false;
        }

        if (!is_null($location->enemy_strength_type) && $character->currentAutomations()->where('type', AutomationType::EXPLORING)->get()->isNotEmpty()) {
            event(new ServerMessageEvent($character->user, 'No. You are currently auto battling and the monsters here are different. Stop auto battling, then enter, then begin again.'));

            return false;
        }

        $item = Item::where('id', $location->required_quest_item_id)->first();

        if (!is_null($item)) {
            $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

            if (is_null($slot)) {
                event(new ServerMessageEvent($character->user, 'Cannot enter this location without a ' . $item->name, $item->id, true));

                return false;
            }
        }

        return true;
    }

    /**
     * Can the player move to this location?
     *
     * If the location is not land, we check for the quest item.
     * The exception is purgatory who does not let you move off land.
     *
     * If the player is moving from land to land, then it will
     * still check but always return true.
     *
     * @param Character $character
     * @return bool
     */
    protected function canPlayerMoveToLocation(Character $character): bool {
        $gameMap = $character->map->gameMap;

        if ($gameMap->mapType()->isSurface() || $gameMap->mapType()->isLabyrinth()) {
            return $this->mapTileValue->canWalkOnWater($character, $this->x, $this->y);
        }

        if ($gameMap->mapType()->isDungeons()) {
            return $this->mapTileValue->canWalkOnDeathWater($character, $this->x, $this->y);
        }

        if ($gameMap->mapType()->isHell()) {
            return $this->mapTileValue->canWalkOnMagma($character, $this->x, $this->y);
        }

        if ($gameMap->mapType()->isPurgatory()) {
            return $this->mapTileValue->canWalkOnPurgatoryWater($character, $this->x, $this->y);
        }

        return false;
    }

    /**
     * Updates the monster list when a player enters a special location.
     *
     * @param Character $character
     * @param Location|null $location
     * @return void
     */
    protected function updateMonstersList(Character $character, ?Location $location = null): void {
        $monsters = Cache::get('monsters')[$character->map->gameMap->name];

        if (!is_null($location)) {
            if (!is_null($location->enemy_strength_type)) {
                $monsters = Cache::get('monsters')[$location->name];

                event(new ServerMessageEvent($character->user, 'You have entered a special location.
                Special locations are places where only specific quest items can drop. You can click View Location Details
                to read more about the location and click the relevant help docs link in the modal to read more about special locations.
                Exploring here will NOT allow the location specific quest items to drop. Monsters here are stronger then outside the location.'
                ));
            }
        }

        event(new UpdateMonsterList($monsters, $character->user));
    }

    /**
     * Generate cannot walk message.
     *
     * @param Character $character
     * @return void
     */
    protected function generateCannotWalkServerMessage(Character $character): void {
        $gameMap = $character->map->gameMap;
        $baseMessage = 'You are missing a required item to do that. Item you are missing is: ';

        if ($gameMap->mapType()->isSurface() || $gameMap->mapType()->isLabyrinth()) {
            $this->createServerMessageForCannotWalk($character->user, ItemEffectsValue::WALK_ON_WATER, $baseMessage);

            return;
        }

        if ($gameMap->mapType()->isDungeons()) {
            $this->createServerMessageForCannotWalk($character->user, ItemEffectsValue::WALK_ON_DEATH_WATER, $baseMessage);

            return;
        }

        if ($gameMap->mapType()->isHell()) {
            $this->createServerMessageForCannotWalk($character->user, ItemEffectsValue::WALK_ON_MAGMA, $baseMessage);

            return;
        }

        if ($gameMap->mapType()->isPurgatory()) {
            event(new ServerMessageEvent($character->user, 'You would slip away into the void if you tried to go that way, child!'));
        }
    }

    /**
     * Generate a server message when missing the required quest item.
     *
     * @param User $user
     * @param int $itemType
     * @param string $baseMessage
     * @return void
     */
    private function createServerMessageForCannotWalk(User $user, int $itemType, string $baseMessage): void {
        $itemNeeded = Item::where('effect', $itemType)->first();

        $baseMessage .= $itemNeeded->name;

        event(new ServerMessageEvent($user, $baseMessage, $itemNeeded->id, true));
    }
}
