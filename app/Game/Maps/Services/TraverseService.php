<?php

namespace App\Game\Maps\Services;

use App\Flare\Jobs\CharacterAttackTypesCacheBuilderWithDeductions;
use App\Flare\Models\Map;
use App\Game\Battle\Events\UpdateCharacterStatus;
use Illuminate\Support\Facades\Cache;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Facades\App\Flare\Cache\CoordinatesCache;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\Location;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Maps\Events\MoveTimeOutEvent;
use App\Game\Maps\Events\UpdateGlobalCharacterCountBroadcast;
use App\Game\Maps\Events\UpdateMonsterList;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Maps\Events\UpdateMapBroadcast;
use App\Flare\Events\ServerMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent as MessageEvent;
use App\Flare\Models\GameMap;
use App\Flare\Models\Character;
use App\Flare\Transformers\MonsterTransformer;
use App\Flare\Values\ItemEffectsValue;

class TraverseService {

    /**
     * @var Manager $manager
     */
    private $manager;

    /**
     * @var MonsterTransformer $monsterTransformer
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

    /**
     * @var BuildCharacterAttackTypes  $buildCharacterAttackTypes
     */
    private BuildCharacterAttackTypes $buildCharacterAttackTypes;

    /**
     * TraverseService constructor.
     *
     * @param Manager $manager
     * @param CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer
     * @param BuildCharacterAttackTypes $buildCharacterAttackTypes
     * @param MonsterTransformer $monsterTransformer
     * @param LocationService $locationService
     * @param MapTileValue $mapTileValue
     */
    public function __construct(
        Manager $manager,
        CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer,
        BuildCharacterAttackTypes $buildCharacterAttackTypes,
        MonsterTransformer $monsterTransformer,
        LocationService $locationService,
        MapTileValue $mapTileValue
    ) {
        $this->manager                           = $manager;
        $this->characterSheetBaseInfoTransformer = $characterSheetBaseInfoTransformer;
        $this->buildCharacterAttackTypes         = $buildCharacterAttackTypes;
        $this->monsterTransformer                = $monsterTransformer;
        $this->locationService                   = $locationService;
        $this->mapTileValue                      = $mapTileValue;
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

        if ($gameMap->mapType()->isLabyrinth()) {
            $hasItem = $character->inventory->slots->filter(function($slot) {
                return $slot->item->effect === ItemEffectsValue::LABYRINTH;
            })->all();

            return !empty($hasItem);
        }

        if ($gameMap->mapType()->isDungeons()) {
            $hasItem = $character->inventory->slots->filter(function($slot) {
                return $slot->item->effect === ItemEffectsValue::DUNGEON;
            })->all();

            return !empty($hasItem);
        }

        if ($gameMap->mapType()->isShadowPlane()) {
            $hasItem = $character->inventory->slots->filter(function($slot) {
                return $slot->item->effect === ItemEffectsValue::SHADOWPLANE;
            })->all();

            return !empty($hasItem);
        }

        if ($gameMap->mapType()->isHell()) {
            $hasItem = $character->inventory->slots->filter(function($slot) {
                return $slot->item->effect === ItemEffectsValue::HELL;
            })->all();

            return !empty($hasItem);
        }

        if ($gameMap->mapType()->isPurgatory()) {
            $hasItem = $character->inventory->slots->filter(function($slot) {
                return $slot->item->effect === ItemEffectsValue::PURGATORY;
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
        $this->updateCharacterTimeOut($character);

        $oldMap = $character->map->gameMap;

        $this->updateCharactersPosition($character, $mapId);

        $this->updateMap($character);
        $this->updateActions($mapId, $character, $oldMap);

        $message = 'You have traveled to: ' . $character->map->gameMap->name;

        event(new ServerMessageEvent($character->user, 'plane-transfer', $message));

        $gameMap = $character->map->gameMap;

        if ($gameMap->mapType()->isShadowPlane()) {
            $message = 'As you enter into the Shadow Plane, all you see for miles around are
            shadowy figures moving across the land. The color of the land is grey and lifeless. But you
            feel the presence of death as it creeps ever closer.
            (Characters can walk on water here.)';

            event(new MessageEvent($character->user,  $message));

            event(new GlobalMessageEvent('The gates have opened for: ' . $character->name . '. They have entered the realm of shadows!'));
        }

        if ($gameMap->mapType()->isHell()) {
            $message = 'The stench of sulfur fills your nose. The heat of the magma oceans bathes over you. Demonic shadows and figures move about the land. Tormented souls cry out in anguish!';

            event(new MessageEvent($character->user,  $message));

            event(new GlobalMessageEvent('Hell\'s gates swing wide for: ' . $character->name . '. May the light of The Poet, be their guide through such darkness!'));
        }

        if ($gameMap->mapType()->isPurgatory()) {
            $message = 'The silence of death fills your very being and chills you to bone. Nothing moves amongst the decay and death of this land.';

            event(new MessageEvent($character->user,  $message));

            event(new GlobalMessageEvent('Thunder claps in the sky: ' . $character->name . ' has called forth The Creator\'s gates of despair! The Creator is Furious! "Hear me, child! I shall face you in the depths of my despair and crush the soul from your bones!" the lands fall silent, the children no longer have faith and the fabric of time rips open...'));
        }
    }

    /**
     * Updates the position of the character on the map.
     *
     * If the character is on a map tile where they do not have access, such as water, we move them off it
     * and keep doing this till we fnd land.
     *
     * @param Character $character
     * @param int $mapId
     * @return void
     */
    protected function updateCharactersPosition(Character $character, int $mapId) {
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

        if ($newXPosition !== $xPosition || $newYPosition !== $yPosition) {
            event(new ServerMessageEvent($character->user, 'moved-location', 'Your character was moved as you are missing the appropriate quest item or were not allowed to enter the area.'));
        }
    }

    /**
     * Change the players' location if they cannot walk on the planes water.
     *
     * We do this till we find ground.
     *
     * @param Character $character
     * @param array $cache
     * @return Character
     */
    protected function changeLocation(Character $character, array $cache) {

        $x = $cache['x'];
        $y = $cache['y'];

        if (!$this->mapTileValue->canWalkOnWater($character, $character->map->character_position_x, $character->map->character_position_y) ||
            !$this->mapTileValue->canWalkOnDeathWater($character, $character->map->character_position_x, $character->map->character_position_y) ||
            !$this->mapTileValue->canWalkOnMagma($character, $character->map->character_position_x, $character->map->character_position_y) ||
            $this->mapTileValue->isPurgatoryWater($this->mapTileValue->getTileColor($character, $character->map->character_position_x, $character->map->character_position_y))
        ) {

            $character->map()->update([
                'character_position_x' => $x[rand(0, count($x) - 1)],
                'character_position_y' => $y[rand(0, count($y) - 1)],
            ]);

            return $this->changeLocation($character->refresh(), $cache);
        }

        $location = Location::where('x', $character->map->character_position_x)->where('y', $character->map->character_position_y)->where('game_map_id', $character->map->game_map_id)->first();

        if (!is_null($location)) {
            if (!$location->can_players_enter) {
                $character->map()->update([
                    'character_position_x' => $x[rand(0, count($x) - 1)],
                    'character_position_y' => $y[rand(0, count($y) - 1)],
                ]);

                return $this->changeLocation($character->refresh(), $cache);
            }
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

        event(new UpdateCharacterStatus($character));

        event(new MoveTimeOutEvent($character, 10, false, true));
    }

    /**
     * Update character map-actions.
     *
     * @param int $mapId
     * @param Character $character
     */
    public function updateActions(int $mapId, Character $character, GameMap $oldGameMap) {
        $user         = $character->user;
        $gameMap      = GameMap::find($mapId);

        $this->updateActionsForMap($gameMap, $oldGameMap, $character);

        $monsters = $this->getMonstersForMap($character->map, $mapId);

        $characterBaseStats = new Item($character, $this->characterSheetBaseInfoTransformer);

        $characterBaseStats = $this->manager->createData($characterBaseStats)->toArray();

        event(new UpdateBaseCharacterInformation($user, $characterBaseStats));

        event(new UpdateTopBarEvent($character));

        event(new UpdateMonsterList($monsters, $user));
    }

    /**
     * Updates the character attack data based on map type.
     *
     * @param GameMap $gameMap
     * @param GameMap $oldGameMap
     * @param Character $character
     * @return void
     */
    protected function updateActionsForMap(GameMap $gameMap, GameMap $oldGameMap, Character $character): void {
        if ($gameMap->mapType()->isShadowPlane()) {
            $this->updateActionTypeCache($character, $gameMap->enemy_stat_bonus);
        } else if ($gameMap->mapType()->isHell()) {
            $this->updateActionTypeCache($character, $gameMap->enemy_stat_bonus);
        } else if ($gameMap->mapType()->isPurgatory()) {
            $this->updateActionTypeCache($character, $gameMap->enemy_stat_bonus);
        } else if ($oldGameMap->mapType()->isPurgatory() || $oldGameMap->mapType()->isHell() || $oldGameMap->mapType()->isShadowPlane()) {
            $this->updateActionTypeCache($character, 0.0);
        }
    }

    protected function getMonstersForMap(Map $characterMap, int $mapId): array {
        $locationWithEffect   = Location::whereNotNull('enemy_strength_type')
            ->where('x', $characterMap->character_position_x)
            ->where('y', $characterMap->character_position_y)
            ->where('game_map_id', $characterMap->game_map_id)
            ->first();

        if (!is_null($locationWithEffect)) {
            return Cache::get('monsters')[$locationWithEffect->name];
        }

        return Cache::get('monsters')[GameMap::find($mapId)->name];
    }

    /**
     * Update the map-actions cache.
     *
     * @param Character $character
     * @param float $deduction
     * @return void
     */
    protected function updateActionTypeCache(Character $character, float $deduction) {
        CharacterAttackTypesCacheBuilderWithDeductions::dispatch($character, $deduction);
    }

    /**
     * Update the map to reflect the new plane.
     *
     * @param Character $character
     */
    protected function updateMap(Character $character) {
        event(new UpdateMapBroadcast($character->user));
    }

    /**
     * When the character traverses, let's update the global character count for all planes.
     *
     * @param int $oldMap
     */
    protected function updateGlobalCharacterMapCount(int $oldMap) {
        $maps = GameMap::where('id', '=', $oldMap)->get();

        foreach ($maps as $map) {
            event(new UpdateGlobalCharacterCountBroadcast($map));
        }

        $maps = GameMap::where('id', '!=', $oldMap)->get();

        foreach ($maps as $map) {
            event(new UpdateGlobalCharacterCountBroadcast($map));
        }
    }
}
