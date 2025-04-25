<?php

namespace App\Game\Maps\Services;

use App\Flare\Cache\CoordinatesCache;
use App\Flare\Models\CelestialFight;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Location;
use App\Flare\Models\Raid;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class LocationService
{
    use CanPlayerMassEmbezzle, KingdomCache, LiveCharacterCount, UpdateRaidMonstersForLocation;

    private CoordinatesCache $coordinatesCache;

    private CharacterCacheData $characterCacheData;

    private UpdateCharacterAttackTypesHandler $updateCharacterAttackTypes;

    /**
     * @var ?Location | null
     */
    private ?Location $location;

    /**
     * @var bool | false
     */
    private bool $canSettle = false;

    private bool $isEventBasedUpdate = false;

    public function __construct(CoordinatesCache $coordinatesCache, CharacterCacheData $characterCacheData, UpdateCharacterAttackTypesHandler $updateCharacterAttackTypes)
    {
        $this->coordinatesCache = $coordinatesCache;
        $this->characterCacheData = $characterCacheData;
        $this->updateCharacterAttackTypes = $updateCharacterAttackTypes;
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
//            'map_url' => Storage::disk('maps')->url($character->map_url),
//            'character_map' => $character->map,
//            'locations' => $this->fetchLocationData($character)->merge($this->fetchCorruptedLocationData($raid)),
//            'can_move' => $character->can_move,
//            'can_move_again_at' => $character->can_move_again_at,
//            'coordinates' => $this->coordinatesCache->getFromCache(),
//            'celestial_id' => $this->getCelestialEntityId($character),
//            'can_settle_kingdom' => $this->canSettle,
            'character_kingdoms' => $this->getKingdoms($character),
//            'npc_kingdoms' => Kingdom::select('id', 'x_position', 'y_position', 'npc_owned', 'name')->whereNull('character_id')->where('game_map_id', $character->map->game_map_id)->where('npc_owned', true)->get(),
//            'other_kingdoms' => $this->getEnemyKingdoms($character),
//            'characters_on_map' => $this->getActiveUsersCountForMap($character),
//            'lockedLocationType' => is_null($lockedLocation) ? null : $lockedLocation->type,
//            'is_event_based' => $this->isEventBasedUpdate,
//            'can_access_hell_forged_shop' => $character->map->gameMap->mapType()->isHell(),
//            'can_access_purgatory_chains_shop' =>  $character->map->gameMap->mapType()->isPurgatory(),
//            'can_access_twisted_earth_shop' => $character->map->gameMap->mapType()->isTwistedMemories(),
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
    public function fetchLocationData(Character $character): Collection
    {
        $locations = Location::with('questRewardItem')->where('game_map_id', $character->map->game_map_id)->get();

        return $this->transformLocationData($locations);
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
     * @param  ?Raid  $raid
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
