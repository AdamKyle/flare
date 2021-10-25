<?php

namespace App\Game\Maps\Services;

use App\Flare\Services\BuildCharacterAttackTypes;
use App\Game\Core\Events\UpdateAttackStats;
use App\Game\Maps\Events\MoveTimeOutEvent;
use App\Game\Maps\Events\UpdateGlobalCharacterCountBroadcast;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Support\Facades\Cache;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Facades\App\Flare\Cache\CoordinatesCache;
use App\Game\Maps\Events\UpdateMapBroadcast;
use App\Game\Maps\Events\UpdateActionsBroadcast;
use App\Flare\Events\ServerMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent as MessageEvent;
use App\Flare\Models\GameMap;
use App\Flare\Models\Character;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Transformers\MonsterTransfromer;
use App\Flare\Values\ItemEffectsValue;
use League\Fractal\Resource\Item as ResourceItem;

class TraverseService {

    /**
     * @var Manager $manager
     */
    private $manager;

    /**
     * @var CharacterAttackTransformer $characterAttackTransformer
     */
    private $characterAttackTransformer;

    /**
     * @var MonsterTransfromer $monsterTransformer
     */
    private $monsterTransformer;

    /**
     * @var LocationService $locationService
     */
    private $locationService;

    /**
     * @var MapTileValue $mapTileValue
     */
    private $mapTileValue;

    private $buildCharacterAttackTypes;

    /**
     * TraverseService constructor.
     *
     * @param Manager $manager
     * @param CharacterAttackTransformer $characterAttackTransformer
     * @param MonsterTransfromer $monsterTransformer
     * @param LocationService $locationService
     */
    public function __construct(
        Manager $manager,
        CharacterAttackTransformer $characterAttackTransformer,
        BuildCharacterAttackTypes $buildCharacterAttackTypes,
        MonsterTransfromer $monsterTransformer,
        LocationService $locationService,
        MapTileValue $mapTileValue
    ) {
        $this->manager                    = $manager;
        $this->characterAttackTransformer = $characterAttackTransformer;
        $this->buildCharacterAttackTypes  = $buildCharacterAttackTypes;
        $this->monsterTransformer         = $monsterTransformer;
        $this->locationService            = $locationService;
        $this->mapTileValue               = $mapTileValue;
    }

    /**
     * Can you travel to another plane?
     *
     * @param int $mapId
     * @param Character $character
     * @return bool
     */
    public function canTravel(int $mapId, Character $character): bool {
        $gameMap = GameMap::find($mapId);

        if ($gameMap->name === 'Labyrinth') {
            $hasItem = $character->inventory->slots->filter(function($slot) {
                return $slot->item->effect === ItemEffectsValue::LABYRINTH;
            })->all();

            return !empty($hasItem);
        }

        if ($gameMap->name === 'Dungeons') {
            $hasItem = $character->inventory->slots->filter(function($slot) {
                return $slot->item->effect === ItemEffectsValue::DUNGEON;
            })->all();

            return !empty($hasItem);
        }

        if ($gameMap->name === 'Shadow Plane') {
            $hasItem = $character->inventory->slots->filter(function($slot) {
                return $slot->item->effect === ItemEffectsValue::SHADOWPLANE;
            })->all();

            return !empty($hasItem);
        }

        if ($gameMap->name === 'Surface') {
            return true;
        }

        return false;
    }

    /**
     * Travel to another plane of existence.
     *
     * @param int $mapId
     * @param Character $character
     */
    public function travel(int $mapId, Character $character) {
        $oldMap = $character->map->game_map_id;

        $character->map()->update([
            'game_map_id' => $mapId
        ]);

        $character = $character->refresh();

        $xPosition = $character->map->character_position_x;
        $yPosition = $character->map->character_position_y;

        $cache = CoordinatesCache::getFromCache();

        $character = $this->changeLocation($character, $cache);

        $newXPosition = $character->map->character_position_x;
        $newYPosition = $character->map->character_position_y;

        if ($newXPosition !== $xPosition && $newYPosition !== $yPosition) {
            $color = $this->mapTileValue->getTileColor($character, $xPosition, $yPosition);

            if ($this->mapTileValue->isWaterTile($color) || $this->mapTileValue->isDeathWaterTile($color)) {
                event(new ServerMessageEvent($character->user, 'moved-location', 'Your character was moved as you are missing the appropriate quest item.'));
            }
        }

        $this->updateGlobalCharacterMapCount($oldMap);
        $this->updateMap($character);
        $this->updateActions($mapId, $character);
        $this->updateCharacterTimeOut($character);

        $message = 'You have traveled to: ' . $character->map->gameMap->name;

        event(new ServerMessageEvent($character->user, 'plane-transfer', $message));

        $name = $character->map->gameMap->name;

        if ($name === 'Shadow Plane') {
            $message = 'As you enter into the Shadow Plane, all you see for miles around are 
            shadowy figures moving across the land. The color of the land is grey and lifeless. But you 
            feel the presence of death as it creeps ever closer. 
            (Characters can walk on water here, monster strength is increased by 25% including Devouring Light. You are reduced by 25% while here.)';

            event(new MessageEvent($character->user,  $message));

            event(new GlobalMessageEvent('The gates have opened for: ' . $character->name . '. They have entered the realm of shadows!'));
        }
    }

    protected function changeLocation(Character $character, array $cache) {

        if (!$this->mapTileValue->canWalkOnWater($character, $character->map->character_position_x, $character->map->character_position_y) ||
            !$this->mapTileValue->canWalkOnDeathWater($character, $character->map->character_position_x, $character->map->character_position_y)
        ) {

            $x = $cache['x'];
            $y = $cache['y'];

            $character->map()->update([
                'character_position_x' => $x[rand(0, count($x) - 1)],
                'character_position_y' => $y[rand(0, count($y) - 1)],
            ]);

            return $this->changeLocation($character->refresh(), $cache);
        }

        return $character->refresh();
    }

    /**
     * Set the timeout for the character.
     *
     * @param Character $character
     */
    protected function updateCharacterTimeOut(Character $character) {
        $character->update([
            'can_move'          => false,
            'can_move_again_at' => now()->addSeconds(10),
        ]);

        event(new MoveTimeOutEvent($character));
    }

    /**
     * Update character actions.
     *
     * @param int $mapId
     * @param Character $character
     */
    protected function updateActions(int $mapId, Character $character) {
        $user      = $character->user;

        $character = new Item($character, $this->characterAttackTransformer);
        $monsters  = Cache::get('monsters')[GameMap::find($mapId)->name];

        $character = $this->manager->createData($character)->toArray();

        broadcast(new UpdateActionsBroadcast($character, $monsters, $user));

        event(new UpdateAttackStats($character, $user));
    }

    /**
     * Update the map to reflect the new plane.
     *
     * @param Character $character
     */
    protected function updateMap(Character $character) {
        broadcast(new UpdateMapBroadcast($this->locationService->getLocationData($character->refresh()), $character->user));
    }

    /**
     * When the character traverses, lets update the global character count for all planes.
     *
     * @param int $oldMap
     */
    protected function updateGlobalCharacterMapCount(int $oldMap) {
        $maps = GameMap::where('id', '=', $oldMap)->get();

        foreach ($maps as $map) {
            broadcast(new UpdateGlobalCharacterCountBroadcast($map));
        }

        $maps = GameMap::where('id', '!=', $oldMap)->get();

        foreach ($maps as $map) {
            broadcast(new UpdateGlobalCharacterCountBroadcast($map));
        }
    }
}
