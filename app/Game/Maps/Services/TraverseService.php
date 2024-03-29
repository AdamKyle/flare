<?php

namespace App\Game\Maps\Services;

use App\Flare\Models\Map;
use League\Fractal\Manager;
use App\Flare\Models\GameMap;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Location;
use App\Flare\Models\Character;
use League\Fractal\Resource\Item;
use App\Game\Maps\Events\UpdateMap;
use Illuminate\Support\Facades\Cache;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Maps\Events\MoveTimeOutEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Maps\Events\UpdateMonsterList;
use Facades\App\Flare\Cache\CoordinatesCache;
use App\Flare\Transformers\MonsterTransformer;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Maps\Services\Common\UpdateRaidMonstersForLocation;
use App\Flare\Jobs\CharacterAttackTypesCacheBuilderWithDeductions;
use App\Flare\Models\Event;

class TraverseService {

    use UpdateRaidMonstersForLocation;

    /**
     * @var Manager $manager
     */
    private Manager $manager;

    /**
     * @var MonsterTransformer $monsterTransformer
     */
    private MonsterTransformer $monsterTransformer;

    /**
     * @var LocationService $locationService
     */
    private LocationService $locationService;

    /**
     * @var MapTileValue $mapTileValue
     */
    private MapTileValue $mapTileValue;

    /**
     * @var BuildCharacterAttackTypes  $buildCharacterAttackTypes
     */
    private BuildCharacterAttackTypes $buildCharacterAttackTypes;

    /**
     * @var CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer
     */
    private CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer;

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
            $hasItem = $character->inventory->slots->filter(function ($slot) {
                return $slot->item->effect === ItemEffectsValue::LABYRINTH;
            })->all();

            return !empty($hasItem);
        }

        if ($gameMap->mapType()->isDungeons()) {
            $hasItem = $character->inventory->slots->filter(function ($slot) {
                return $slot->item->effect === ItemEffectsValue::DUNGEON;
            })->all();

            return !empty($hasItem);
        }

        if ($gameMap->mapType()->isShadowPlane()) {
            $hasItem = $character->inventory->slots->filter(function ($slot) {
                return $slot->item->effect === ItemEffectsValue::SHADOWPLANE;
            })->all();

            return !empty($hasItem);
        }

        if ($gameMap->mapType()->isHell()) {
            $hasItem = $character->inventory->slots->filter(function ($slot) {
                return $slot->item->effect === ItemEffectsValue::HELL;
            })->all();

            return !empty($hasItem);
        }

        if ($gameMap->mapType()->isPurgatory()) {
            $hasItem = $character->inventory->slots->filter(function ($slot) {
                return $slot->item->effect === ItemEffectsValue::PURGATORY;
            })->all();

            return !empty($hasItem);
        }

        if (!is_null($gameMap->only_during_event_type)) {
            $event = Event::where('type', $gameMap->only_during_event_type)->first();

            if (is_null($event)) {
                return false;
            }

            return true;
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
    public function travel(int $mapId, Character $character): void {
        $this->updateCharacterTimeOut($character);

        $oldMap = $character->map->gameMap;

        $this->updateCharactersPosition($character, $mapId);

        $this->updateMap($character);
        $this->updateActions($mapId, $character, $oldMap);
        $this->updateKingdomOwnedKingdom($character);

        $character = $character->refresh();

        $location = $this->getLocationForCoordinates($character);

        $this->updateMonstersList($character, $location);

        $message = 'You have traveled to: ' . $character->map->gameMap->name;

        ServerMessageHandler::handleMessage($character->user, 'plane_transfer', $message);

        $gameMap = $character->map->gameMap;

        if ($gameMap->mapType()->isShadowPlane()) {
            $message = 'As you enter into the Shadow Plane, all you see for miles around are
            shadowy figures moving across the land. The color of the land is grey and lifeless. But you
            feel the presence of death as it creeps ever closer.
            (Characters can walk on water here.)';

            event(new ServerMessageEvent($character->user,  $message));

            event(new GlobalMessageEvent('The gates have opened for: ' . $character->name . '. They have entered the realm of shadows!'));
        }

        if ($gameMap->mapType()->isHell()) {
            $message = 'The stench of sulfur fills your nose. The heat of the magma oceans bathes over you. Demonic shadows and figures move about the land. Tormented souls cry out in anguish!';

            event(new ServerMessageEvent($character->user,  $message));

            event(new GlobalMessageEvent('Hell\'s gates swing wide for: ' . $character->name . '. May the light of The Poet, be their guide through such darkness!'));
        }

        if ($gameMap->mapType()->isPurgatory()) {
            $message = 'The silence of death fills your very being and chills you to bone. Nothing moves amongst the decay and death of this land.';

            event(new ServerMessageEvent($character->user,  $message));

            event(new GlobalMessageEvent('Thunder claps in the sky: ' . $character->name . ' has called forth The Creator\'s gates of despair! The Creator is Furious! "Hear me, child! I shall face you in the depths of my despair and crush the soul from your bones!" the lands fall silent, the children no longer have faith and the fabric of time rips open...'));
        }

        if ($gameMap->mapType()->isTheIcePlane()) {
            $message = 'The air becomes bitter and cold, the ice starts to form on the ground around you. Everything seems so frozen in place.';

            event(new ServerMessageEvent($character->user,  $message));

            event(new GlobalMessageEvent('"Have you seen my son?" the call of the Ice Queen is heard across the lands of Tlessa. The Poet turns in his study: "So she has breached our reality."'));
        }
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

        return Location::where('x', $character->map->character_position_x)->where('y', $character->map->character_position_y)->where('game_map_id', $gameMapId)->first();
    }

    /**
     * Update the players kingdom at specified location.
     *
     * @param Character $character
     * @return void
     */
    protected function updateKingdomOwnedKingdom(Character $character): void {
        $mapId = $character->map->game_map_id;

        $x = $character->map->character_position_x;
        $y = $character->map->character_position_y;

        Kingdom::where('x_position', $x)
            ->where('y_position', $y)
            ->where('character_id', $character->id)
            ->where('game_map_id', $mapId)
            ->update([
                'last_walked' => now(),
            ]);
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
    protected function updateCharactersPosition(Character $character, int $mapId): void {
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
            ServerMessageHandler::handleMessage($character->user, 'moved_location', 'Your character was moved as you are missing the appropriate quest item or were not allowed to enter the area.');
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
    protected function changeLocation(Character $character, array $cache): Character {

        $x = $cache['x'];
        $y = $cache['y'];

        if (
            !$this->mapTileValue->canWalkOnWater($character, $character->map->character_position_x, $character->map->character_position_y) ||
            !$this->mapTileValue->canWalkOnDeathWater($character, $character->map->character_position_x, $character->map->character_position_y) ||
            !$this->mapTileValue->canWalkOnMagma($character, $character->map->character_position_x, $character->map->character_position_y) ||
            $this->mapTileValue->isPurgatoryWater((int) $this->mapTileValue->getTileColor($character, $character->map->character_position_x, $character->map->character_position_y))
        ) {
            // Update the players location, call the method again to validate that we are not at a invalid location.
            // repeat until we are in a non-invalid location,
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
     * @return Character
     */
    protected function updateCharacterTimeOut(Character $character): Character {
        $character->update([
            'can_move'          => false,
            'can_move_again_at' => now()->addSeconds(10),
        ]);

        $character = $character->refresh();

        event(new UpdateCharacterStatus($character));

        event(new MoveTimeOutEvent($character, 10, false, true));

        return $character;
    }

    /**
     * Update character map-actions.
     *
     * @param int $mapId
     * @param Character $character
     * @param GameMap $oldGameMap
     */
    public function updateActions(int $mapId, Character $character, GameMap $oldGameMap): void {
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
            $this->updateActionTypeCache($character, $gameMap->character_attack_reduction);
        } else if ($gameMap->mapType()->isHell()) {
            $this->updateActionTypeCache($character, $gameMap->character_attack_reduction);
        } else if ($gameMap->mapType()->isPurgatory()) {
            $this->updateActionTypeCache($character, $gameMap->character_attack_reduction);
        } else if ($gameMap->mapType()->isTheIcePlane()) {
            $this->updateActionTypeCache($character, $gameMap->character_attack_reduction);
        } else if (
            $oldGameMap->mapType()->isPurgatory() ||
            $oldGameMap->mapType()->isHell() ||
            $oldGameMap->mapType()->isShadowPlane() ||
            $oldGameMap->mapType()->isTheIcePlane()
        ) {
            $this->updateActionTypeCache($character, 0.0);
        }
    }

    protected function getMonstersForMap(Map $characterMap, int $mapId): array {
        $locationWithEffect   = Location::whereNotNull('enemy_strength_type')
            ->where('x', $characterMap->character_position_x)
            ->where('y', $characterMap->character_position_y)
            ->where('game_map_id', $characterMap->game_map_id)
            ->first();

        $canAccessPurgatory = $characterMap->character->inventory->slots->where('items.effect', ItemEffectsValue::PURGATORY)->count() > 0;

        $monsters = Cache::get('monsters')[GameMap::find($mapId)->name];

        if (!is_null($locationWithEffect)) {

            if ($characterMap->gameMap->only_during_event_type && $canAccessPurgatory) {
                return Cache::get('monsters')[$locationWithEffect->name];
            }

            return $monsters['easier'];
        }

        if ($characterMap->gameMap->only_during_event_type) {
            if ($canAccessPurgatory) {
                $monsters = $monsters['regular'];
            } else {
                $monsters = $monsters['easier'];
            }
        }

        return $monsters;
    }

    /**
     * Update the map-actions cache.
     *
     * @param Character $character
     * @param float $deduction
     * @return void
     */
    protected function updateActionTypeCache(Character $character, float $deduction): void {
        CharacterAttackTypesCacheBuilderWithDeductions::dispatch($character, $deduction);
    }

    /**
     * Update the map to reflect the new plane.
     *
     * @param Character $character
     */
    protected function updateMap(Character $character): void {
        event(new UpdateMap($character->user));
    }
}
