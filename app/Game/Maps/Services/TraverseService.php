<?php

namespace App\Game\Maps\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Location;
use App\Flare\Models\Map;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Character\Builders\AttackBuilders\Jobs\CharacterAttackTypesCacheBuilderWithDeductions;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Character\CharacterSheet\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Gems\Progression\Services\CharacterAreaGemEffectService;
use App\Game\Maps\Events\MoveTimeOutEvent;
use App\Game\Maps\Events\UpdateMap;
use App\Game\Maps\Events\UpdateMonsterList;
use App\Game\Maps\Jobs\UpdateMapLocationsJob;
use App\Game\Maps\Services\Common\UpdateRaidMonstersForLocation;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\MovementMessageTypes;
use App\Game\Monsters\Services\MonsterListService;
use App\Game\Monsters\Transformers\MonsterTransformer;
use Facades\App\Game\Maps\Cache\CoordinatesCache;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Support\Facades\Cache;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class TraverseService
{
    use UpdateRaidMonstersForLocation;

    private Manager $manager;

    private MonsterTransformer $monsterTransformer;

    private LocationService $locationService;

    private MapTileValue $mapTileValue;

    private BuildCharacterAttackTypes $buildCharacterAttackTypes;

    private CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer;

    private MonsterListService $monsterListService;

    public function __construct(
        Manager $manager,
        CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer,
        BuildCharacterAttackTypes $buildCharacterAttackTypes,
        MonsterTransformer $monsterTransformer,
        MonsterListService $monsterListService,
        LocationService $locationService,
        MapTileValue $mapTileValue,
        private readonly RandomNumberGenerator $randomNumberGenerator,
        private readonly CharacterAreaGemEffectService $characterAreaGemEffectService,
    ) {
        $this->manager = $manager;
        $this->characterSheetBaseInfoTransformer = $characterSheetBaseInfoTransformer;
        $this->buildCharacterAttackTypes = $buildCharacterAttackTypes;
        $this->monsterTransformer = $monsterTransformer;
        $this->locationService = $locationService;
        $this->mapTileValue = $mapTileValue;
        $this->monsterListService = $monsterListService;
    }

    /**
     * Can you travel to another plane?
     */
    public function canTravel(int $mapId, Character $character): bool
    {
        $gameMap = GameMap::find($mapId);

        if (is_null($gameMap)) {
            return false;
        }

        if ($gameMap->isGeneratedGemMap() || ! $gameMap->can_traverse) {
            return false;
        }

        $mapType = $gameMap->mapType();

        if ($mapType->isLabyrinth()) {
            return $character->inventory->slots()
                ->whereHas('item', function ($query) {
                    $query->where('effect', ItemEffectType::LABYRINTH->value);
                })
                ->exists();
        }

        if ($mapType->isDungeons()) {
            return $character->inventory->slots()
                ->whereHas('item', function ($query) {
                    $query->where('effect', ItemEffectType::DUNGEON->value);
                })
                ->exists();
        }

        if ($mapType->isShadowPlane()) {
            return $character->inventory->slots()
                ->whereHas('item', function ($query) {
                    $query->where('effect', ItemEffectType::SHADOW_PLANE->value);
                })
                ->exists();
        }

        if ($mapType->isHell()) {
            return $character->inventory->slots()
                ->whereHas('item', function ($query) {
                    $query->where('effect', ItemEffectType::HELL->value);
                })
                ->exists();
        }

        if ($mapType->isPurgatory()) {
            return $character->inventory->slots()
                ->whereHas('item', function ($query) {
                    $query->where('effect', ItemEffectType::PURGATORY->value);
                })
                ->exists();
        }

        if (! is_null($gameMap->only_during_event_type)) {
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
     */
    public function travel(int $mapId, Character $character): void
    {
        $this->updateCharacterTimeOut($character);

        $oldMap = $character->map->gameMap;

        $this->updateCharactersPosition($character, $mapId);

        $character = $character->refresh();

        $this->updateMap($character);
        $this->updateActions($mapId, $character, $oldMap);
        $this->updateKingdomOwnedKingdom($character);

        $character = $character->refresh();

        $location = $this->getLocationForCoordinates($character);

        $this->updateMonstersList($character, $location);

        $message = 'You have traveled to: '.$character->map->gameMap->name;

        ServerMessageHandler::handleMessage($character->user, MovementMessageTypes::PLANE_TRANSFER, $message);

        $gameMap = $character->map->gameMap;

        if (! $gameMap->isGeneratedGemMap()) {
            $this->sendPlaneNarrativeMessages($character, $gameMap);
        }

        event(new UpdateCharacterStatus($character));
    }

    /**
     * Send the special parent-plane narrative/global messages for the closed set of narrative
     * Game Map types (Shadow Plane, Hell, Purgatory, The Ice Plane, Twisted Memories, and
     * Delusional Memories). Never called for a generated Gem Map destination.
     */
    private function sendPlaneNarrativeMessages(Character $character, GameMap $gameMap): void
    {
        if ($gameMap->mapType()->isShadowPlane()) {
            $message = 'As you enter into the Shadow Plane, all you see for miles around are
            shadowy figures moving across the land. The color of the land is grey and lifeless. But you
            feel the presence of death as it creeps ever closer.
            (Characters can walk on water here.)';

            event(new ServerMessageEvent($character->user, $message));

            event(new GlobalMessageEvent('The gates have opened for: '.$character->name.'. They have entered the realm of shadows!'));
        }

        if ($gameMap->mapType()->isHell()) {
            $message = 'The stench of sulfur fills your nose. The heat of the magma oceans bathes over you. Demonic shadows and figures move about the land. Tormented souls cry out in anguish!';

            event(new ServerMessageEvent($character->user, $message));

            event(new GlobalMessageEvent('Hell\'s gates swing wide for: '.$character->name.'. May the light of The Poet, be their guide through such darkness!'));
        }

        if ($gameMap->mapType()->isPurgatory()) {
            $message = 'The silence of death fills your very being and chills you to bone. Nothing moves amongst the decay and death of this land.';

            event(new ServerMessageEvent($character->user, $message));

            event(new GlobalMessageEvent('Thunder claps in the sky: '.$character->name.' has called forth The Creator\'s gates of despair! The Creator is Furious! "Hear me, child! I shall face you in the depths of my despair and crush the soul from your bones!" the lands fall silent, the children no longer have faith and the fabric of time rips open...'));
        }

        if ($gameMap->mapType()->isTheIcePlane()) {
            $message = 'The air becomes bitter and cold, the ice starts to form on the ground around you. Everything seems so frozen in place.';

            event(new ServerMessageEvent($character->user, $message));

            event(new GlobalMessageEvent('"Have you seen my son?" the call of the Ice Queen is heard across the lands of Tlessa. The Poet turns in his study: "So she has breached our reality."'));
        }

        if ($gameMap->mapType()->isTwistedMemories()) {
            $message = 'Your mind becomes a fog as you enter into a land where even your own thoughts become twisted into a darkness never before experienced by mortals before.';

            event(new ServerMessageEvent($character->user, $message));

            event(new GlobalMessageEvent('"She is the reason the world is trapped in these lies." '.$character->name.' enters into a place where their own heart becomes a memory that is twisted into hate.'));
        }

        if ($gameMap->mapType()->isDelusionalMemories()) {
            $message = 'The delusions of a mad man are heavy on the air here ...';

            event(new ServerMessageEvent($character->user, $message));

            event(new GlobalMessageEvent('"Fliniguss has gone mad."  the Red Hawk Soldier states. "Help us put him down!" '.$character->name.' enters into a place where the war of the ages past never ended.'));
        }
    }

    /**
     * Returns the location at the coordinates the player wants to move too.
     *
     * - Location can be null.
     */
    protected function getLocationForCoordinates(Character $character): ?Location
    {
        $gameMapId = $character->map->game_map_id;

        return Location::where('x', $character->map->character_position_x)->where('y', $character->map->character_position_y)->where('game_map_id', $gameMapId)->first();
    }

    /**
     * Update the players kingdom at specified location.
     */
    protected function updateKingdomOwnedKingdom(Character $character): void
    {
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
     */
    protected function updateCharactersPosition(Character $character, int $mapId): void
    {
        $destinationGameMap = GameMap::find($mapId);

        if (is_null($destinationGameMap)) {
            return;
        }

        $cache = CoordinatesCache::getFromCache();

        $xCoordinates = $cache['x'];
        $yCoordinates = $cache['y'];

        $xMaxIndex = count($xCoordinates) - 1;
        $yMaxIndex = count($yCoordinates) - 1;

        $this->mapTileValue->setUp($character, $destinationGameMap);

        $candidateX = $xCoordinates[$this->randomNumberGenerator->numberBetween(0, $xMaxIndex)];
        $candidateY = $yCoordinates[$this->randomNumberGenerator->numberBetween(0, $yMaxIndex)];

        $didReroll = false;

        while (true) {
            if ($this->mapTileValue->canWalk($candidateX, $candidateY)) {
                $location = Location::where('x', $candidateX)
                    ->where('y', $candidateY)
                    ->where('game_map_id', $mapId)
                    ->first();

                if (is_null($location) || $location->can_players_enter) {
                    break;
                }
            }

            $didReroll = true;

            $candidateX = $xCoordinates[$this->randomNumberGenerator->numberBetween(0, $xMaxIndex)];
            $candidateY = $yCoordinates[$this->randomNumberGenerator->numberBetween(0, $yMaxIndex)];
        }

        $character->map()->update([
            'game_map_id' => $mapId,
            'character_position_x' => $candidateX,
            'character_position_y' => $candidateY,
        ]);

        if ($didReroll) {
            ServerMessageHandler::handleMessage($character->user, MovementMessageTypes::MOVE_LOCATION, 'Your character was moved as you are missing the appropriate quest item or were not allowed to enter the area.');
        }
    }

    /**
     * Set the timeout for the character.
     */
    protected function updateCharacterTimeOut(Character $character): Character
    {
        $character->update([
            'can_move' => false,
            'can_move_again_at' => now()->addSeconds(10),
        ]);

        $character = $character->refresh();

        event(new UpdateCharacterStatus($character));

        event(new MoveTimeOutEvent($character, 10, false, true));

        return $character;
    }

    /**
     * Update character map-actions.
     */
    public function updateActions(int $mapId, Character $character, GameMap $oldGameMap): void
    {
        $user = $character->user;
        $gameMap = GameMap::find($mapId);

        $this->updateActionsForMap($gameMap, $oldGameMap, $character);

        $monsters = $this->monsterListService->getMonstersForCharacterAsList($character);

        $characterBaseStats = new Item($character, $this->characterSheetBaseInfoTransformer);

        $characterBaseStats = $this->manager->createData($characterBaseStats)->toArray();

        event(new UpdateBaseCharacterInformation($user, $characterBaseStats));

        event(new UpdateMonsterList($monsters, $user));
    }

    /**
     * Updates the character attack data based on map type.
     */
    protected function updateActionsForMap(GameMap $gameMap, GameMap $oldGameMap, Character $character): void
    {
        $gemReduction = $this->characterAreaGemEffectService->resolveForCharacter($character)->characterPowerReduction();

        if ($this->isCharacterReductionMapType($gameMap)) {
            $this->updateActionTypeCache($character, ($gameMap->character_attack_reduction ?? 0.0) + $gemReduction);

            return;
        }

        if ($this->isCharacterReductionMapType($oldGameMap)) {
            $this->updateActionTypeCache($character, $gemReduction);

            return;
        }

        if ($gemReduction > 0.0) {
            $this->updateActionTypeCache($character, $gemReduction);
        }
    }

    /**
     * Determine whether the given Game Map belongs to the closed set of Map types that apply a
     * Character attack reduction (Shadow Plane, Hell, Purgatory, The Ice Plane, Twisted Memories,
     * and Delusional Memories).
     */
    private function isCharacterReductionMapType(GameMap $gameMap): bool
    {
        return $gameMap->mapType()->isShadowPlane()
            || $gameMap->mapType()->isHell()
            || $gameMap->mapType()->isPurgatory()
            || $gameMap->mapType()->isTheIcePlane()
            || $gameMap->mapType()->isTwistedMemories()
            || $gameMap->mapType()->isDelusionalMemories();
    }

    protected function getMonstersForMap(Map $characterMap, int $mapId): array
    {
        $canAccessPurgatory = $characterMap->character->inventory->slots->where('items.effect', ItemEffectType::PURGATORY->value)->count() > 0;

        $monsters = Cache::get('monsters')[GameMap::find($mapId)->name];

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
     */
    protected function updateActionTypeCache(Character $character, float $deduction): void
    {
        CharacterAttackTypesCacheBuilderWithDeductions::dispatch($character, $deduction)->delay(now()->addSeconds(2));
    }

    /**
     * Update the map to reflect the new plane.
     */
    protected function updateMap(Character $character): void
    {
        UpdateMapLocationsJob::dispatch($character->id)->delay(now()->addSecond());

        event(new UpdateMap($character->user, false));
    }
}
