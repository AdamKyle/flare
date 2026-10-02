<?php

namespace App\Game\Maps\Services;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Item as ItemModel;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Location;
use App\Flare\Models\Map;
use App\Flare\Models\Monster;
use App\Flare\Models\Raid;
use App\Flare\Pagination\Pagination;
use App\Flare\Transformers\Serializer\PlainDataSerializer;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Core\Items\Transformers\QuestItemTransformer;
use App\Game\Core\Traits\KingdomCache;
use App\Game\Maps\Cache\CoordinatesCache;
use App\Game\Maps\Events\UpdateLocationBasedCraftingOptions;
use App\Game\Maps\Events\UpdateLocationBasedEventGoals;
use App\Game\Maps\Services\Common\CanPlayerMassEmbezzle;
use App\Game\Maps\Services\Common\LiveCharacterCount;
use App\Game\Maps\Services\Common\UpdateRaidMonstersForLocation;
use App\Game\Maps\Transformers\CondensedKingdomTransformer;
use App\Game\Maps\Transformers\LocationsTransformer;
use App\Game\Monsters\Services\MonsterListService;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Storage;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection as LeagueCollection;

class LocationService
{
    use CanPlayerMassEmbezzle, KingdomCache, LiveCharacterCount, UpdateRaidMonstersForLocation;

    private bool $canSettle = false;

    private bool $isEventBasedUpdate = false;

    private ?Location $location = null;

    /**
     * @param CoordinatesCache $coordinatesCache
     * @param CharacterCacheData $characterCacheData
     * @param UpdateCharacterAttackTypesHandler $updateCharacterAttackTypes
     * @param QuestItemTransformer $questItemTransformer
     * @param LocationsTransformer $locationTransformer
     * @param CondensedKingdomTransformer $condensedKingdomTransformer
     * @param PlainDataSerializer $plainArraySerializer
     * @param Pagination $pagination
     * @param Manager $manager
     * @param MonsterListService $monsterListService
     */
    public function __construct(
        private readonly CoordinatesCache $coordinatesCache,
        private readonly CharacterCacheData $characterCacheData,
        private readonly UpdateCharacterAttackTypesHandler $updateCharacterAttackTypes,
        private readonly QuestItemTransformer $questItemTransformer,
        private readonly LocationsTransformer $locationTransformer,
        private readonly CondensedKingdomTransformer $condensedKingdomTransformer,
        private readonly PlainDataSerializer $plainArraySerializer,
        private readonly Pagination $pagination,
        private readonly Manager $manager,
        private readonly MonsterListService $monsterListService,
    ) {}

    /**
     * Build the Character's current map tiles, position, movement timeout, locations, and kingdoms.
     *
     * @param Character $character
     * @param Raid|null $raid
     * @return array
     */
    public function getMapData(Character $character, ?Raid $raid = null): array
    {
        $this->locationBasedEvents($character);

        $gameMap = $character->map->gameMap;

        $this->kingdomManagement($gameMap);

        return [
            'tiles' => $gameMap->tile_map ?? [],
            'character_position' => $this->getCharacterPositionData($character->map),
            'time_out_details' => $this->getMapTimeOutDetails($character),
            'locations' => $this->fetchLocationData($character),
            'coordinates' => $this->coordinatesCache->getFromCache(),
            'character_kingdoms' => $this->getKingdoms($character),
            'npc_kingdoms' => $this->getNpcKingdoms($character),
            'enemy_kingdoms' => $this->getCondensedEnemyKingdoms($character),
            'has_conjurable_celestials' => Monster::conjurableOnMap($character->map->game_map_id)->exists(),
        ];
    }

    /**
     * Build the Character's map broadcast payload, optionally including the map's locations and raid-corrupted locations.
     *
     * @param Character $character
     * @param bool $includeLocationData
     * @param Raid|null $raid
     * @return array
     */
    public function getLocationData(Character $character, bool $includeLocationData = true, ?Raid $raid = null): array
    {
        $this->locationBasedEvents($character);

        $this->kingdomManagement($character->map->gameMap);

        $lockedLocation = $this->getLockedLocation($character);

        $map = $character->map->gameMap;

        $locationData = [
            'map_url' => Storage::disk('maps')->url($character->map_url),
            'locations' => [],
            'character_map' => $character->map,
            'can_move' => $character->can_move,
            'can_move_again_at' => $character->can_move_again_at,
            'coordinates' => $this->coordinatesCache->getFromCache(),
            'celestial_id' => $this->getCelestialEntityId($character),
            'can_settle_kingdom' => $this->canSettle,
            'my_kingdoms' => $this->getKingdoms($character),
            'npc_kingdoms' => Kingdom::select('id', 'x_position', 'y_position', 'npc_owned', 'name')->whereNull('character_id')->where('game_map_id', $character->map->game_map_id)->where('npc_owned', true)->get(),
            'other_kingdoms' => $this->getEnemyKingdoms($character),
            'characters_on_map' => $this->getActiveUsersCountForMap($character),
            'lockedLocationType' => $lockedLocation?->type,
            'is_event_based' => $this->isEventBasedUpdate,
            'can_access_hell_forged_shop' => $map->mapType()->isHell(),
            'can_access_purgatory_chains_shop' => $map->mapType()->isPurgatory(),
            'can_access_twisted_earth_shop' => $map->mapType()->isTwistedMemories(),
            'can_access_hell_forged' => $map->mapType()->isHell(),
            'can_access_purgatory_chains' => $map->mapType()->isPurgatory(),
            'can_access_twisted_memories' => $map->mapType()->isTwistedMemories(),
        ];

        if ($includeLocationData) {
            $locationData['locations'] = array_merge($this->fetchLocationData($character), $this->fetchCorruptedLocationData($raid));
        }

        return $locationData;
    }

    /**
     * Return the paginated quest Items that drop at the Location, filtered by name.
     *
     * @param Location $location
     * @param int $perPage
     * @param int $page
     * @param string $searchText
     * @return array
     */
    public function getDroppableItems(Location $location, int $perPage = 10, int $page = 1, string $searchText = ''): array
    {
        $items = ItemModel::where('drop_location_id', $location->id)->where('name', 'LIKE', '%'.$searchText.'%')->get();

        return $this->pagination->buildPaginatedDate($items, $this->questItemTransformer, $perPage, $page);
    }

    /**
     * Return the quest Item required to access the Game Map, when it has one.
     *
     * @param GameMap $gameMap
     * @return array|null
     */
    public function getMapRequiredItem(GameMap $gameMap): ?array
    {
        $requiredItem = $gameMap->requiredItem();

        if (is_null($requiredItem)) {
            return null;
        }

        return $this->questItemTransformer->transform($requiredItem);
    }

    /**
     * Return the Location's factual details, including its Game Map and any supplied Gem context.
     *
     * @param Location $location
     * @param array|null $gemContext
     * @return array
     */
    public function getLocationDetails(Location $location, ?array $gemContext = null): array
    {
        return [
            'id' => $location->id,
            'name' => $location->name,
            'game_map' => [
                'id' => $location->map->id,
                'name' => $location->map->name,
            ],
            'description' => $location->description,
            'can_players_enter' => $location->can_players_enter,
            'can_auto_battle' => $location->can_auto_battle,
            'location_type' => 'location',
            'is_corrupted' => $location->is_corrupted,
            'quest_reward_item' => [
                'data' => is_null($location->questRewardItem) ? null : $this->questItemTransformer->transform($location->questRewardItem),
            ],
            'required_quest_item' => is_null($location->requiredQuestItem) ? null : $this->questItemTransformer->transform($location->requiredQuestItem),
            'gem_context' => $gemContext,
            'x' => $location->x,
            'y' => $location->y,
        ];
    }

    /**
     * Return the kingdoms, locations, and coordinates the Character can teleport to.
     *
     * @param Character $character
     * @return array
     */
    public function getTeleportLocations(Character $character): array
    {
        return [
            'character_kingdoms' => $this->getKingdoms($character),
            'npc_kingdoms' => $this->getNpcKingdoms($character),
            'enemy_kingdoms' => [],
            'locations' => $this->fetchLocationData($character),
            'coordinates' => $this->coordinatesCache->getFromCache(),
        ];
    }

    /**
     * Return the Character's map position.
     *
     * @param Map $map
     * @return array
     */
    public function getCharacterPositionData(Map $map): array
    {
        return [
            'x_position' => $map->character_position_x,
            'y_position' => $map->character_position_y,
        ];
    }

    /**
     * Fire the location-based status, crafting, event goal, and Monster list updates for the Character's position.
     *
     * @param Character $character
     * @return void
     */
    public function locationBasedEvents(Character $character): void
    {
        $this->processLocation($character);

        event(new UpdateCharacterStatus($character));
        event(new UpdateLocationBasedCraftingOptions($character->user));
        event(new UpdateLocationBasedEventGoals($character->user));

        $this->updateMonstersList($character, $this->location);
    }

    /**
     * Fetch location data for a character based on their map.
     *
     * @param Character $character
     * @return array
     */
    public function fetchLocationData(Character $character): array
    {
        $gameMap = $character->map->gameMap;

        $locations = Location::where('game_map_id', $gameMap->id)->get();

        $this->manager->setSerializer($this->plainArraySerializer);

        $locationData = new LeagueCollection($locations, $this->locationTransformer);

        return $this->manager->createData($locationData)->toArray();
    }

    /**
     * Fetch the corrupted locations for a raid.
     *
     * @param Raid|null $raid
     * @return array
     */
    public function fetchCorruptedLocationData(?Raid $raid): array
    {
        if (is_null($raid)) {
            return [];
        }

        $locations = Location::where('is_corrupted', true)->whereIn('id', $raid->corrupted_location_ids)->get();

        $this->manager->setSerializer($this->plainArraySerializer);

        $locationData = new LeagueCollection($locations, $this->locationTransformer);

        return $this->manager->createData($locationData)->toArray();
    }

    /**
     * Return the quest-item-locked Location at the Character's position, when there is one.
     *
     * @param Character $character
     * @return Location|null
     */
    private function getLockedLocation(Character $character): ?Location
    {
        return Location::where('x', $character->map->character_position_x)
            ->where('y', $character->map->character_position_y)
            ->where('game_map_id', $character->map->game_map_id)
            ->whereNotNull('required_quest_item_id')
            ->first();
    }

    /**
     * Return the condensed rows for NPC-owned kingdoms on the Character's map.
     *
     * @param Character $character
     * @return array
     */
    private function getNpcKingdoms(Character $character): array
    {
        $kingdoms = Kingdom::whereNull('character_id')
            ->where('game_map_id', $character->map->game_map_id)
            ->where('npc_owned', true)
            ->get();

        return $kingdoms->map(fn (Kingdom $kingdom): array => $this->condensedKingdomTransformer->transform($kingdom))->all();
    }

    /**
     * Return the condensed rows for other characters' kingdoms on the same map.
     *
     * @param Character $character
     * @return array
     */
    private function getCondensedEnemyKingdoms(Character $character): array
    {
        $kingdoms = Kingdom::where('game_map_id', $character->map->game_map_id)
            ->where('npc_owned', false)
            ->whereNotNull('character_id')
            ->where('character_id', '!=', $character->id)
            ->get();

        return $kingdoms->map(fn (Kingdom $kingdom): array => $this->condensedKingdomTransformer->transform($kingdom))->all();
    }

    /**
     * Return whether the Character can move and how many seconds remain on their movement timeout.
     *
     * @param Character $character
     * @return array
     */
    private function getMapTimeOutDetails(Character $character): array
    {
        $canMoveAgainAt = $character->can_move_again_at;
        $timeLeft = is_null($canMoveAgainAt) ? 0 : max(0, now()->diffInSeconds($character->can_move_again_at));

        return [
            'can_move' => $character->can_move,
            'time_left' => $timeLeft,
            'show_timer' => $timeLeft > 0,
        ];
    }

    /**
     * Return the id of the accessible Celestial fight at the Character's position, when there is one.
     *
     * @param Character $character
     * @return int|null
     */
    private function getCelestialEntityId(Character $character): ?int
    {
        return CelestialFight::with('monster')
            ->accessibleToCharacter($character)
            ->join('monsters', fn (JoinClause $join): JoinClause => $join->on('monsters.id', 'celestial_fights.monster_id')
                ->where('celestial_fights.x_position', $character->map->character_position_x)
                ->where('celestial_fights.y_position', $character->map->character_position_y)
                ->where('monsters.game_map_id', $character->map->gameMap->id))
            ->select('celestial_fights.id')
            ->first()
            ?->id;
    }

    /**
     * Resolve the Location at the Character's position.
     *
     * @param Character $character
     * @return void
     */
    private function processLocation(Character $character): void
    {
        $this->location = Location::where('x', $character->x_position)
            ->where('y', $character->y_position)
            ->where('game_map_id', $character->map->game_map_id)
            ->first();
    }

    /**
     * Allow settling a kingdom when the Character is not on a Location and not inside a generated Gem World.
     *
     * @param GameMap $gameMap
     * @return void
     */
    private function kingdomManagement(GameMap $gameMap): void
    {
        if (is_null($this->location) && ! $gameMap->isGeneratedGemMap()) {
            $this->canSettle = true;
        }
    }
}
