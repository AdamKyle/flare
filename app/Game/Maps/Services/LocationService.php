<?php

namespace App\Game\Maps\Services;

use App\Flare\Cache\CoordinatesCache;
use App\Flare\Models\CelestialFight;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Location;
use App\Flare\Models\Map;
use App\Flare\Models\Raid;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Transformers\Serializer\PlainDataSerializer;
use App\Flare\Values\LocationEffectValue;
use App\Flare\Values\LocationType;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Core\Traits\KingdomCache;
use App\Game\Maps\Events\UpdateLocationBasedCraftingOptions;
use App\Game\Maps\Events\UpdateLocationBasedEventGoals;
use App\Game\Maps\Services\Common\CanPlayerMassEmbezzle;
use App\Game\Maps\Services\Common\LiveCharacterCount;
use App\Game\Maps\Services\Common\UpdateRaidMonstersForLocation;
use App\Game\Maps\Transformers\LocationsTransformer;
use Illuminate\Support\Collection;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection as LeagueCollection;

class LocationService
{
    use CanPlayerMassEmbezzle, KingdomCache, LiveCharacterCount, UpdateRaidMonstersForLocation;

    /**
     * @var bool | false
     */
    private bool $canSettle = false;

    private bool $isEventBasedUpdate = false;

    public function __construct(private readonly CoordinatesCache                  $coordinatesCache,
                                private readonly CharacterCacheData                $characterCacheData,
                                private readonly UpdateCharacterAttackTypesHandler $updateCharacterAttackTypes,
                                private readonly LocationsTransformer              $locationTransformer,
                                private readonly PlainDataSerializer               $plainArraySerializer,
                                private readonly Manager                           $manager)
    {
    }

    public function setIsEventBasedUpdate(bool $isEventBased): LocationService
    {
        $this->isEventBasedUpdate = $isEventBased;

        return $this;
    }

    /**
     * Get location data
     */
    public function getLocationData(Character $character, ?Raid $raid = null): array
    {

        $this->locationBasedEvents($character);

        $this->kingdomManagement($character);

        $lockedLocation = $this->getLockedLocation($character);

        $gameMap = $character->map->gameMap;

        return [
            'tiles' => $gameMap->tile_map,
            'character_position' => $this->getCharacterPositionData($character->map),
            'time_out_details' => $this->getMapTimeOutDetails($character),
            'locations' => $this->fetchLocationData($character),
            'coordinates' => $this->coordinatesCache->getFromCache(),
//            'celestial_id' => $this->getCelestialEntityId($character),
//            'can_settle_kingdom' => $this->canSettle,
            'character_kingdoms' => $this->getKingdoms($character),
            'npc_kingdoms' => $this->getNpcKingdoms($character),
            'enemy_kingdoms' => $this->getEnemyKingdoms($character),
//            'characters_on_map' => $this->getActiveUsersCountForMap($character),
//            'lockedLocationType' => is_null($lockedLocation) ? null : $lockedLocation->type,
//            'is_event_based' => $this->isEventBasedUpdate,
//            'can_access_hell_forged_shop' => $character->map->gameMap->mapType()->isHell(),
//            'can_access_purgatory_chains_shop' =>  $character->map->gameMap->mapType()->isPurgatory(),
//            'can_access_twisted_earth_shop' => $character->map->gameMap->mapType()->isTwistedMemories(),
        ];
    }

    public function getTeleportLocations(Character $character): array {
        return [
            'character_kingdoms' => $this->getKingdoms($character),
            'npc_kingdoms' => $this->getNpcKingdoms($character),
            'enemy_kingdoms' => $this->getEnemyKingdoms($character),
            'locations' => $this->fetchLocationData($character),
            'coordinates' => $this->coordinatesCache->getFromCache(),
        ];
    }

    protected function getNpcKingdoms(Character $character): array {
        return Kingdom::select('id', 'x_position', 'y_position', 'name')
            ->whereNull('character_id')
            ->where('game_map_id', $character->map->game_map_id)
            ->where('npc_owned', true)
            ->get()
            ->toArray();
    }


    public function getCharacterPositionData(Map $map): array {
        return [
            'x_position' => $map->character_position_x,
            'y_position' => $map->character_position_y,
        ];
    }

    private function getMapTimeOutDetails(Character $character): array {
        $canMoveAgainAt = $character->can_move_again_at;
        $timeLeft = is_null($canMoveAgainAt) ? 0 : max(0, now()->diffInSeconds($character->can_move_again_at));

        return [
            'can_move' => $character->can_move,
            'time_left' => $timeLeft,
            'show_timer' => $timeLeft > 0,
        ];
    }

    /**
     * Fire off location based events.
     */
    public function locationBasedEvents(Character $character): void
    {
        $this->processLocation($character);

        // In case automation is running, this way the timer updates.
        event(new UpdateCharacterStatus($character));

        // Update location based crafting options:
        event(new UpdateLocationBasedCraftingOptions($character->user));

        // Update location based event goals
        event(new UpdateLocationBasedEventGoals($character->user));

        // Update monsters for a possible raid at a possible location
        $this->updateMonstersForRaid($character, $this->location);

        // Update monsters for a specific location type
        $this->updateMonsterForLocationType($character, $this->location);
    }

    /**
     * Fetch the locations for this map the characters on.
     */
    public function fetchLocationData(Character $character): array
    {

        $gameMap = $character->map->gameMap;

        $locations = Location::with('questRewardItem')->where('game_map_id', $gameMap->id)->get();

        $this->manager->setSerializer($this->plainArraySerializer);

        $locationData = new LeagueCollection($locations, $this->locationTransformer);

        return $this->manager->createData($locationData)->toArray();
    }

    /**
     * Fetch locations based on map.
     */
    public function fetchLocationsForMap(GameMap $map): Collection
    {
        $locations = Location::with('questRewardItem')->where('game_map_id', $map->id)->get();

        return $this->transformLocationData($locations);
    }

    /**
     * Fetch corrupted locatuions based on the raid.
     *
     * If no raid is set, return an empty collection.
     *
     * @param  ?Raid $raid
     * @return Collection
     */
    public function fetchCorruptedLocationData(?Raid $raid = null): Collection
    {

        if (is_null($raid)) {
            return collect();
        }

        $corruptedLocationIds = $raid->corrupted_location_ids;

        array_push($corruptedLocationIds, $raid->raid_boss_location_id);

        $locations = Location::whereIn('id', $corruptedLocationIds)->get();

        return $this->transformLocationData($locations);
    }

    /**
     * Add additional data to the location data.
     */
    protected function transformLocationData(Collection $locations): Collection
    {
        return $locations->transform(function ($location) {

            $location->increases_enemy_stats_by = null;
            $location->increase_enemy_percentage_by = null;
            $location->type_name = null;

            if (! is_null($location->type)) {
                if ((new LocationType($location->type))->isPurgatorySmithHouse()) {
                    $location->type_name = 'Purgatory Smiths House';
                }

                if ((new LocationType($location->type))->isUnderWaterCaves()) {
                    $location->type_name = 'Underwater Caves';
                }

                if ((new LocationType($location->type))->isAlchemyChurch()) {
                    $location->type_name = 'Alchemy Church';
                }
            }

            if (! is_null($location->enemy_strength_type)) {
                $location->increases_enemy_stats_by = LocationEffectValue::getIncreaseByAmount($location->enemy_strength_type);
                $location->increase_enemy_percentage_by = LocationEffectValue::fetchPercentageIncrease($location->enemy_strength_type);

                if (! is_null($location->type)) {
                    $locationType = new LocationType($location->type);

                    if ($locationType->isGoldMines()) {
                        $location->type_name = 'Gold Mines';
                    }

                    if ($locationType->isPurgatoryDungeons()) {
                        $location->type_name = 'Purgatory Dungeons';
                    }

                    if ($locationType->isTheOldChurch()) {
                        $location->type_name = 'The Old Church';
                    }
                }
            }

            $location->required_quest_item_name = null;

            if (! is_null($location->required_quest_item_id)) {
                $location->required_quest_item_name = $location->requiredQuestItem->name;
            }

            $location->game_map_name = $location->map->name;

            return $location;
        });
    }

    /**
     * Is there a celestial entity at the characters' location?
     */
    protected function getCelestialEntityId(Character $character): ?int
    {

        $fight = CelestialFight::with('monster')->join('monsters', function ($join) use ($character) {
            $join->on('monsters.id', 'celestial_fights.monster_id')
                ->where('celestial_fights.x_position', $character->map->character_position_x)
                ->where('celestial_fights.y_position', $character->map->character_position_y)
                ->where('monsters.game_map_id', $character->map->gameMap->id);
        })->select('celestial_fights.id')->first();

        if (! is_null($fight)) {
            return $fight->id;
        }

        return null;
    }

    /**
     * Processes the location.
     *
     * We will fetch the location information for the character position.
     *
     * This includes port details and any relevant adventures the location might have.
     */
    protected function processLocation(Character $character): void
    {
        $this->location = Location::where('x', $character->x_position)
            ->where('y', $character->y_position)
            ->where('game_map_id', $character->map->game_map_id)
            ->first();
    }

    /**
     * Determines the action the player can take.
     *
     * Based on the character position, if there is a kingdom or not.
     * We determine the action the player can take. That is, can they settle?
     * Can they attack the kingdom or can they manage the kingdom?
     */
    protected function kingdomManagement(Character $character): void
    {
        if (is_null($this->location)) {
            $this->canSettle = true;
        }
    }

    /**
     * Gets locked location details.
     */
    protected function getLockedLocation(Character $character): ?Location
    {
        return Location::where('x', $character->map->character_position_x)
            ->where('y', $character->map->character_position_y)
            ->where('game_map_id', $character->map->game_map_id)
            ->whereNotNull('required_quest_item_id')
            ->first();
    }
}
