<?php

namespace App\Game\Maps\Adventure\Services;

use App\Flare\Cache\CoordinatesCache;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Location;
use App\Game\Maps\Adventure\Events\MoveTimeOutEvent;
use App\Game\Maps\Adventure\Events\UpdateMapDetailsBroadcast;
use App\Game\Maps\Adventure\Values\MapTileValue;
use App\Game\Maps\Values\MapPositionValue;

class MovementService {

    /**
     * @var array $portDetails
     */
    private $portDetails = [];

    /**
     * @var array $adventureDetails
     */
    private $adventureDetails = [];

    /**
     * @var array $kingdomData
     */
    private $kingdomData = [];

    /**
     * @var PortService $portService
     */
    private $portService;

    /**
     * @var MapTileValue $mapTileValue
     */
    private $mapTile;

    /**
     * @var CoordinatesCache $coordinatesCache
     */
    private $coordinatesCache;

    /**
     * Constructor
     * 
     * @param PortService $portService
     * @return void
     */
    public function __construct(PortService $portService, 
                                MapTileValue $mapTile, 
                                CoordinatesCache $coordinatesCache,
                                MapPositionValue $mapPositionValue) 
    {
        $this->portService      = $portService;
        $this->mapTile          = $mapTile;
        $this->coordinatesCache = $coordinatesCache;
        $this->mapPositionValue = $mapPositionValue;
    }

    /**
     * Porcess the area.
     * 
     * sets the kingdom data for a specific area.
     * 
     * This includes if you are the owner, can settle, can manage or can attack.
     * 
     * @param int $x
     * @param int $y
     * @param Character $character
     */
    public function processArea(int $x, int $y, Character $character): void {
        $location = Location::where('x', $x)->where('y', $y)->first();

        if (!is_null($location)) {
            $this->processLocation($location, $character);
        }

        $kingdom = Kingdom::where('x_position', $x)->where('y_position', $y)->first();

        $canAttack = false;
        $canSettle = false;
        $canManage = false;

        if (!is_null($kingdom)) {
            if ($kingdom->character->user->id !== auth()->user()->id) {
                $canAttack = true;
            } else {
                $canManage = true;
            }
        } else if (is_null($location)) {
            $canSettle = true;
        }

        $this->kingdomData = [
            'owner'      => is_null($kingdom) ? 'No one' : $kingdom->character->name,
            'can_attack' => $canAttack,
            'can_manage' => $canManage,
            'can_settle' => $canSettle,
        ];
    }


    /**
     * Process the location for ports and adventures as well as drops.
     * 
     * @param Location $location | null
     * @param Character $character
     * @param PortService $portService
     * @return void
     */
    public function processLocation(Location $location, Character $character): void {
        if ($location->is_port) {
            $this->portDetails = $this->portService->getPortDetails($character, $location);
        }

        $this->giveQuestReward($location, $character);

        if ($location->adventures->isNotEmpty()) {
            $this->adventureDetails = $location->adventures->toArray();
        }
    }

    /**
     * Get the port details
     * 
     * @return array
     */
    public function portDetails(): array {
        return $this->portDetails;
    }

    /**
     * Get the adventure details
     * 
     * @return array
     */
    public function adventureDetails(): array {
        return $this->adventureDetails;
    }

    /**
     * Get the kingdom data
     * 
     * @param array
     */
    public function kingdomDetails(): array {
        return $this->kingdomData;
    }

    /**
     * Give the quest reward item
     * 
     * @param Location $location
     * @param Character $character
     * @param void
     */
    public function giveQuestReward(Location $location, Character $character): void {
        if (!is_null($location->questRewardItem)) {
            $item = $character->inventory->slots->filter(function($slot) use ($location) {
                return $slot->item_id === $location->questRewardItem->id;
            })->first();

            if (is_null($item)) {
                
                if (!($character->inventory->slots()->count() < $character->inventory_max)) {
                    event(new ServerMessageEvent($character->user, 'inventory_full'));
                } else {
                    $character->inventory->slots()->create([
                        'inventory_id' => $character->inventory->id,
                        'item_id'      => $location->questRewardItem->id,
                    ]);

                    event(new ServerMessageEvent($character->user, 'found_item', $location->questRewarditem->affix_name));
                }
            }
        }
    }

    /**
     * Teleport the player to a specified location.
     * 
     * The array that is returned is for the response of the controller.
     * 
     * @param Character $character
     * @param int $x
     * @param int $y
     * @param int $cost
     * @param int $time
     * @param int $timeout
     * @return array
     */
    public function teleport(Character $character, int $x, int $y, int $cost, int $timeout): array {
        $canTeleport = $this->canTeleportToWater($character, $x, $y);

        if (!$canTeleport) {
            return [
                'message' => 'Cannot teleport to water locations without a Flask of Fresh Air.',
                'status'  => 422,
            ];
        }

        if ($character->gold < $cost) {
            return [
                'message' => 'Not enough gold.',
                'status'  => 422,
            ];
        }

        $coordinates = $this->coordinatesCache->getFromCache();

        if (!in_array($x, $coordinates['x']) && !in_array($x, $coordinates['y'])) {
            return [
                'message' => 'Invalid input.',
                'status'  => 422,
            ];
        }

        $this->processArea($x, $y, $character);

        $character->update([
            'can_move'          => false,
            'gold'              => $character->gold - $cost,
            'can_move_again_at' => now()->addMinutes($timeout),
        ]);
        
        $character->map()->update([
            'character_position_x' => $x,
            'character_position_y' => $y,
            'position_x'           => $this->mapPositionValue->fetchXPosition($x, $character->map->position_x),
            'position_y'           => $this->mapPositionValue->fetchYPosition($y),
        ]);

        $character = $character->refresh();
        
        event(new MoveTimeOutEvent($character, $timeout, true));
        event(new UpdateTopBarEvent($character));

        event(new UpdateMapDetailsBroadcast($character->map, $character->user, $this));

        return ['message' => null, 'status' => 200];
    }

    protected function canTeleportToWater(Character $character, int $x, int $y) {
        $color = $this->mapTile->getTileColor($character, $x, $y);
        
        if ($this->mapTile->isWaterTile((int) $color)) {
            $hasItem = $character->inventory->slots->filter(function($slot) {
                return $slot->item->effect === 'walk-on-water';
            })->isNotEmpty();

            return $hasItem;
        }

        // We are not water
        return true;
    }
}