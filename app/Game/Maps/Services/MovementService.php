<?php

namespace App\Game\Maps\Services;

use App\Flare\Cache\CoordinatesCache;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\CelestialFight;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Location;
use App\Flare\Models\Npc;
use App\Flare\Services\BuildMonsterCacheService;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\NpcTypes;
use App\Game\Battle\Services\ConjureService;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Traits\CanHaveQuestItem;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Maps\Events\MoveTimeOutEvent;
use App\Game\Maps\Events\UpdateMapBroadcast;
use App\Game\Maps\Events\UpdateMonsterList;
use App\Game\Maps\Services\Common\CanPlayerMassEmbezzle;
use App\Game\Maps\Services\Common\LiveCharacterCount;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Maps\Values\MapPositionValue;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent as GameServerMessageEvent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use League\Fractal\Manager;

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
     * @return void
     */
    public function __construct(PortService $portService,
                                MapTileValue $mapTile,
                                CharacterSheetBaseInfoTransformer $characterAttackTransformer,
                                CoordinatesCache $coordinatesCache,
                                MapPositionValue $mapPositionValue,
                                TraverseService $traverseService,
                                ConjureService $conjureService,
                                BuildMonsterCacheService $buildMonsterCacheService,
                                LocationService $locationService,
                                Manager $manager)
    {
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

        $gameMaps = GameMap::select('id', 'required_location_id', 'name')->get();
        $xPosition = $character->map->character_position_x;
        $yPosition = $character->map->character_position_y;
        $location  = Location::where('x', $xPosition)->where('y', $yPosition)->first();

        return $this->filterTraversableMaps($character, $location, $gameMaps);
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

        $params = [
            'character_position_x' => $character->map->character_position_x,
            'character_position_y' => $character->map->character_position_y,
        ];

        $this->giveLocationReward($character, $params);

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
     * @param Character $character
     * @param Location $location
     * @param Collection $gameMaps
     * @return array
     */
    protected function filterTraversableMaps(Character $character, Location $location, Collection $gameMaps): array {
        foreach ($gameMaps as $index => $gameMap) {
            if (!is_null($gameMap->required_location_id) && !$character->map->gameMap->mapType()->isPurgatory()) {
                if (!is_null($location)) {
                    if ($location->id !== $gameMap->required_location_id) {
                        unset($gameMaps[$index]);
                    }
                } else {
                    unset($gameMaps[$index]);
                }
            }
        }

        return $gameMaps->toArray();
    }
}
