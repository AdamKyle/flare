<?php

namespace App\Game\Maps\Services;

use App\Flare\Cache\CoordinatesCache;
use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Services\BuildMonsterCacheService;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Services\ConjureService;
use App\Game\Core\Traits\CanHaveQuestItem;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Maps\Services\Common\CanPlayerMassEmbezzle;
use App\Game\Maps\Services\Common\LiveCharacterCount;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent as GameServerMessageEvent;
use Illuminate\Database\Eloquent\Collection;
use League\Fractal\Manager;

class MovementService
{
    use CanHaveQuestItem, CanPlayerMassEmbezzle, LiveCharacterCount, ResponseBuilder;

    /**
     * @var PortService
     */
    private $portService;

    /**
     * @var MapTileValue
     */
    private $mapTile;

    /**
     * @var CharacterAttackTransformer
     */
    private $characterAttackTransformer;

    /**
     * @var CoordinatesCache
     */
    private $coordinatesCache;

    /**
     * @var TraverseService
     */
    private $traverseService;

    /**
     * @var ConjureService
     */
    private $conjureService;

    /**
     * @var BuildMonsterCacheService
     */
    private $buildMonsterCacheService;

    private $locationService;

    /**
     * @var Manager
     */
    private $manager;

    private const CHANCE_FOR_CELESTIAL_TO_SPAWN = 1000;

    /**
     * Constructor
     */
    public function __construct(
        PortService $portService,
        MapTileValue $mapTile,
        CharacterSheetBaseInfoTransformer $characterAttackTransformer,
        CoordinatesCache $coordinatesCache,
        TraverseService $traverseService,
        ConjureService $conjureService,
        BuildMonsterCacheService $buildMonsterCacheService,
        LocationService $locationService,
        Manager $manager
    ) {
        $this->portService = $portService;
        $this->mapTile = $mapTile;
        $this->characterAttackTransformer = $characterAttackTransformer;
        $this->coordinatesCache = $coordinatesCache;
        $this->traverseService = $traverseService;
        $this->conjureService = $conjureService;
        $this->buildMonsterCacheService = $buildMonsterCacheService;
        $this->manager = $manager;
        $this->locationService = $locationService;
    }

    /**
     * Access the location service.
     */
    public function accessLocationService(): LocationService
    {
        return $this->locationService;
    }

    /**
     * Get traversable maps for the player.
     */
    public function getMapsToTraverse(Character $character): array
    {

        $gameMaps = GameMap::select('id', 'required_location_id', 'only_during_event_type', 'name', 'can_traverse')->get();
        $xPosition = $character->map->character_position_x;
        $yPosition = $character->map->character_position_y;
        $location = Location::where('x', $xPosition)->where('y', $yPosition)->first();

        return $this->filterTraversableMaps($character, $gameMaps, $location);
    }

    /**
     * Lets the players traverse from one plane to another.
     */
    public function updateCharacterPlane(int $mapId, Character $character): array
    {
        if (! $this->traverseService->canTravel($mapId, $character)) {
            return $this->errorResult('You are missing a required item to travel to that plane.');
        }

        $this->traverseService->travel($mapId, $character);

        $character = $character->refresh();

        $location = Location::where('x', $character->map->character_position_x)
            ->where('y', $character->map->character_position_y)
            ->whereNotNull('quest_reward_item_id')
            ->first();

        if (! is_null($location)) {
            $this->giveLocationReward($character, $location);
        }

        return $this->successResult([
            'can_access_hell_forged_shop' => $character->map->gameMap->mapType()->isHell(),
            'can_access_purgatory_chains_shop' =>  $character->map->gameMap->mapType()->isPurgatory(),
            'can_access_twisted_earth_shop' => $character->map->gameMap->mapType()->isTwistedMemories(),
        ]);
    }

    /**
     * Give the player the location quest reward.
     */
    public function giveLocationReward(Character $character, Location $location): void
    {
        $this->giveQuestReward($character, $location);
    }

    /**
     * Give the player the quest reward item.
     *
     * - Only if the location has a reward item
     * - Only if the player has never had the item before.
     */
    protected function giveQuestReward(Character $character, Location $location): void
    {
        if (! is_null($location->questRewardItem)) {
            $item = $location->questRewardItem;

            if (! $this->canHaveItem($character, $item)) {
                return;
            }

            $slot = $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id' => $location->questRewardItem->id,
            ]);

            $questItem = $location->questRewardItem;

            if (! is_null($questItem->effect)) {
                $message = $character->name.' has found: '.$questItem->affix_name;

                broadcast(new GlobalMessageEvent($message));
            }

            event(new GameServerMessageEvent($character->user, 'You found: '.$questItem->affix_name, $slot->id, true));

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
     */
    protected function filterTraversableMaps(Character $character, Collection $gameMaps, ?Location $location = null): array
    {
        return $gameMaps->reject(function ($gameMap) use ($character, $location) {
            if (! is_null($gameMap->required_location_id) &&
                ! $character->map->gameMap->mapType()->isPurgatory() &&
                (is_null($location) || $location->id !== $gameMap->required_location_id)) {
                return true;
            }

            if (! is_null($gameMap->only_during_event_type) &&
                is_null(Event::where('type', $gameMap->only_during_event_type)->first())) {
                return true;
            }

            return ! $gameMap->can_traverse;
        })->values()->toArray();
    }
}
