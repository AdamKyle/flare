<?php

namespace App\Game\Maps\Services;

use Illuminate\Database\Eloquent\Collection;
use League\Fractal\Manager;
use App\Flare\Cache\CoordinatesCache;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Services\BuildMonsterCacheService;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Battle\Services\ConjureService;
use App\Game\Core\Traits\CanHaveQuestItem;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Maps\Services\Common\CanPlayerMassEmbezzle;
use App\Game\Maps\Services\Common\LiveCharacterCount;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Maps\Values\MapPositionValue;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent as GameServerMessageEvent;

class MovementService {

    use ResponseBuilder, LiveCharacterCount, CanPlayerMassEmbezzle, CanHaveQuestItem;

    /**
     * @var PortService $portService
     */
    private $portService;

    /**
     * @var MapTileValue $mapTileValue
     */
    private $mapTile;

    /**
     * @var CharacterAttackTransformer $characterAttackTransformer
     */
    private $characterAttackTransformer;

    /**
     * @var CoordinatesCache $coordinatesCache
     */
    private $coordinatesCache;

    /**
     * @var MapPositionValue $mapPositionValue
     */
    private $mapPositionValue;

    /**
     * @var TraverseService $traverseService
     */
    private $traverseService;

    /**
     * @var ConjureService $conjureService
     */
    private $conjureService;

    /**
     * @var BuildMonsterCacheService $buildMonsterCacheService
     */
    private $buildMonsterCacheService;

    private $locationService;

    /**
     * @var Manager $manager
     */
    private $manager;

    private const CHANCE_FOR_CELESTIAL_TO_SPAWN = 1000;

    /**
     * Constructor
     *
     * @param PortService $portService
     * @param MapTileValue $mapTile
     * @param CharacterSheetBaseInfoTransformer $characterAttackTransformer
     * @param CoordinatesCache $coordinatesCache
     * @param MapPositionValue $mapPositionValue
     * @param TraverseService $traverseService
     * @param ConjureService $conjureService
     * @param BuildMonsterCacheService $buildMonsterCacheService
     * @param LocationService $locationService
     * @param Manager $manager
     */
    public function __construct(
        PortService $portService,
        MapTileValue $mapTile,
        CharacterSheetBaseInfoTransformer $characterAttackTransformer,
        CoordinatesCache $coordinatesCache,
        MapPositionValue $mapPositionValue,
        TraverseService $traverseService,
        ConjureService $conjureService,
        BuildMonsterCacheService $buildMonsterCacheService,
        LocationService $locationService,
        Manager $manager
    ) {
        $this->portService                = $portService;
        $this->mapTile                    = $mapTile;
        $this->characterAttackTransformer = $characterAttackTransformer;
        $this->coordinatesCache           = $coordinatesCache;
        $this->mapPositionValue           = $mapPositionValue;
        $this->traverseService            = $traverseService;
        $this->conjureService             = $conjureService;
        $this->buildMonsterCacheService   = $buildMonsterCacheService;
        $this->manager                    = $manager;
        $this->locationService            = $locationService;
    }

    /**
     * Access the location service.
     *
     * @return LocationService
     */
    public function accessLocationService(): LocationService {
        return $this->locationService;
    }

    /**
     * Get traversable maps for the player.
     *
     * @param Character $character
     * @return array
     */
    public function getMapsToTraverse(Character $character): array {

        $gameMaps = GameMap::select('id', 'required_location_id', 'only_during_event_type', 'name', 'can_traverse')->get();
        $xPosition = $character->map->character_position_x;
        $yPosition = $character->map->character_position_y;
        $location  = Location::where('x', $xPosition)->where('y', $yPosition)->first();

        return $this->filterTraversableMaps($character, $gameMaps, $location);
    }

    /**
     * Lets the players traverse from one plane to another.
     *
     * @param int $mapId
     * @param Character $character
     * @return array
     */
    public function updateCharacterPlane(int $mapId, Character $character): array {
        if (!$this->traverseService->canTravel($mapId, $character)) {
            return $this->errorResult('You are missing a required item to travel to that plane.');
        }

        $this->traverseService->travel($mapId, $character);

        $character = $character->refresh();

        $location = Location::where('x', $character->map->character_position_x)
            ->where('y', $character->map->character_position_y)
            ->whereNotNull('quest_reward_item_id')
            ->first();

        if (!is_null($location)) {
            $this->giveLocationReward($character, $location);
        }

        return $this->successResult();
    }

    /**
     * Give the player the location quest reward.
     *
     * @param Character $character
     * @param Location $location
     * @return void
     */
    public function giveLocationReward(Character $character, Location $location): void {
        $this->giveQuestReward($character, $location);
    }

    /**
     * Give the player the quest reward item.
     *
     * - Only if the location has a reward item
     * - Only if the player has never had the item before.
     *
     * @param Character $character
     * @param Location $location
     * @return void
     */
    protected function giveQuestReward(Character $character, Location $location): void {
        if (!is_null($location->questRewardItem)) {
            $item = $location->questRewardItem;

            if (!$this->canHaveItem($character, $item)) {
                return;
            }

            $slot = $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id'      => $location->questRewardItem->id,
            ]);

            $questItem = $location->questRewardItem;

            if (!is_null($questItem->effect)) {
                $message = $character->name . ' has found: ' . $questItem->affix_name;

                broadcast(new GlobalMessageEvent($message));
            }

            event(new GameServerMessageEvent($character->user, 'You found: ' . $questItem->affix_name, $slot->id, true));

            event(new UpdateCharacterStatus($character));
        }
    }

    /**
     * Filter out game maps that the player cannot traverse to.
     *
     * - Some maps are hidden from the list unless the player
     *   is physically at the location or on the map.
     *
     * - Some maps are only available during specific events,
     *   therefor we hide them when that event is not running.
     *
     * - Some maps cannot be traversed to through traditional means.
     *
     * @param Character $character
     * @param Location|null $location
     * @param Collection $gameMaps
     * @return array
     */
    protected function filterTraversableMaps(Character $character, Collection $gameMaps, ?Location $location = null): array {
        return $gameMaps->reject(function ($gameMap) use ($character, $location) {
            if (!is_null($gameMap->required_location_id) &&
                !$character->map->gameMap->mapType()->isPurgatory() &&
                (is_null($location) || $location->id !== $gameMap->required_location_id)) {
                return true;
            }

            if (!is_null($gameMap->only_during_event_type) &&
                is_null(Event::where('type', $gameMap->only_during_event_type)->first())) {
                return true;
            }

            return !$gameMap->can_traverse;
        })->values()->toArray();
    }
}
