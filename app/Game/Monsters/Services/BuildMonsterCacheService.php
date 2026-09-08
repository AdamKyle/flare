<?php

namespace App\Game\Monsters\Services;

use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Game\Gems\Services\AreaGemEffectService;
use App\Game\Gems\Values\ResolvedAreaGemEffects;
use App\Game\Maps\Values\LocationType;
use App\Game\Monsters\Transformers\MonsterTransformer;
use App\Game\Monsters\Values\MonsterCacheKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;

/**
 * Builds the Monster fight/list caches. Regular Map and Location Monster
 * caches are transformed using the current rolled Map/Location Gem effects
 * for their gameplay context; Raid, Weekly Fight, and Celestial Monsters
 * remain Gem-neutral.
 */
class BuildMonsterCacheService
{
    public function __construct(
        private readonly Manager $manager,
        private readonly MonsterTransformer $monsterTransformer,
        private readonly AreaGemEffectService $areaGemEffectService,
    ) {}

    /**
     * Build every Monster cache used by gameplay.
     */
    public function buildAll(): void
    {
        $this->buildCache();
        $this->buildLocationCache();
        $this->buildWeeklyFightCache();
        $this->buildRaidCache();
        $this->buildCelestialCache();
    }

    /**
     * Build the regular per-Game-Map Monster cache, applying the current rolled Map Gem
     * effects for each Game Map's actual gameplay context (including generated Gem Worlds).
     */
    public function buildCache(): void
    {
        Cache::delete(MonsterCacheKey::MONSTERS->value);

        $monstersCache = [];

        foreach (GameMap::all() as $gameMap) {
            $monsterSourceMap = $gameMap->monsterSourceGameMap();

            $monsters = new Collection(
                $this->regularMonsterQuery($monsterSourceMap->id)->get(),
                $this->transformerForGameMap($gameMap)
            );

            if (! is_null($monsterSourceMap->only_during_event_type)) {
                $monstersCache[$gameMap->name] = $this->createMonstersForEventMaps($gameMap, $monsters);

                continue;
            }

            $monstersCache[$gameMap->name] = $this->manager->createData($monsters)->toArray();
        }

        Cache::put(MonsterCacheKey::MONSTERS->value, $monstersCache);
    }

    /**
     * Build the per-Location Monster cache: every nongenerated Location with a currently
     * rolled Location Gem (reusing the parent Map's regular Monster population), plus the
     * Cave of Memories dedicated Monster population, which is always Gem-neutral.
     */
    public function buildLocationCache(): void
    {
        Cache::delete(MonsterCacheKey::LOCATION_MONSTERS->value);

        $cache = array_merge(
            $this->buildGemAffectedLocationCache(),
            $this->buildCaveOfMemoriesLocationCache()
        );

        Cache::put(MonsterCacheKey::LOCATION_MONSTERS->value, $cache);
    }

    /**
     * Build the Weekly Fight Monster cache for each authoritative Weekly Fight Location Type.
     * Weekly Fight Monsters never receive Map/Location Gem effects.
     */
    public function buildWeeklyFightCache(): void
    {
        Cache::delete(MonsterCacheKey::WEEKLY_MONSTERS->value);

        $cache = [];
        $transformer = $this->noEffectTransformer();

        foreach (LocationType::weeklyFightLocationTypes() as $locationType) {
            $monsters = new Collection(
                Monster::where('is_celestial_entity', false)
                    ->where('is_raid_monster', false)
                    ->where('is_raid_boss', false)
                    ->where('only_for_location_type', $locationType)
                    ->get(),
                $transformer
            );

            $cache['location-type-'.$locationType] = $this->manager->createData($monsters)->toArray();
        }

        Cache::put(MonsterCacheKey::WEEKLY_MONSTERS->value, $cache);
    }

    /**
     * Build the Raid Monster/Boss cache. Raid Monsters never receive Map/Location Gem effects.
     */
    public function buildRaidCache(): void
    {
        Cache::delete(MonsterCacheKey::RAID_MONSTERS->value);

        $monstersCache = [];
        $transformer = $this->noEffectTransformer();

        foreach (GameMap::all() as $gameMap) {
            $monsterSourceMap = $gameMap->monsterSourceGameMap();

            $raidCritters = Monster::where('is_celestial_entity', false)
                ->where('is_raid_monster', true)
                ->where('is_raid_boss', false)
                ->where('game_map_id', $monsterSourceMap->id)
                ->whereNull('only_for_location_type')
                ->get();

            $raidBosses = Monster::where('is_celestial_entity', false)
                ->where('is_raid_monster', false)
                ->where('is_raid_boss', true)
                ->where('game_map_id', $monsterSourceMap->id)
                ->whereNull('only_for_location_type')
                ->get();

            $monsters = new Collection($raidBosses->merge($raidCritters), $transformer);

            $monstersCache[$gameMap->name] = $this->manager->createData($monsters)->toArray();
        }

        Cache::put(MonsterCacheKey::RAID_MONSTERS->value, $monstersCache);
    }

    /**
     * Build the Celestial Monster cache. Celestials never receive Map/Location Gem effects.
     */
    public function buildCelestialCache(): void
    {
        Cache::delete(MonsterCacheKey::CELESTIALS->value);

        $monstersCache = [];
        $transformer = $this->noEffectTransformer();

        foreach (GameMap::all() as $gameMap) {
            $monsterSourceMap = $gameMap->monsterSourceGameMap();

            $monsters = new Collection(
                Monster::where('is_celestial_entity', true)
                    ->where('game_map_id', $monsterSourceMap->id)
                    ->whereNull('only_for_location_type')
                    ->get(),
                $transformer
            );

            $monstersCache[$gameMap->name] = $this->manager->createData($monsters)->toArray();
        }

        Cache::put(MonsterCacheKey::CELESTIALS->value, $monstersCache);
    }

    /**
     * Delete only the Monster caches whose contents are affected by a Map/Location Gem roll.
     */
    public function invalidateGemAffectedCaches(): void
    {
        Cache::delete(MonsterCacheKey::MONSTERS->value);
        Cache::delete(MonsterCacheKey::LOCATION_MONSTERS->value);
    }

    /**
     * Build Gem-affected Monster cache entries for eligible Locations.
     */
    private function buildGemAffectedLocationCache(): array
    {
        $cache = [];

        $locations = Location::whereHas('gemParamters', fn ($query) => $query->whereNotNull('rolled_gem_id'))
            ->with('gemParamters')
            ->get();

        foreach ($locations as $location) {
            $gameMap = $location->map;

            if (is_null($gameMap) || $gameMap->isGeneratedGemMap()) {
                continue;
            }

            $monsterSourceMap = $gameMap->monsterSourceGameMap();

            $monsters = new Collection(
                $this->regularMonsterQuery($monsterSourceMap->id)->get(),
                $this->monsterTransformer->withAreaGemEffects(
                    $this->areaGemEffectService->resolveForGameMap($gameMap, $location)
                )
            );

            $cache['location-'.$location->id] = $this->manager->createData($monsters)->toArray();
        }

        return $cache;
    }

    /**
     * Build Cave of Memories Monster cache entries by Location id.
     */
    private function buildCaveOfMemoriesLocationCache(): array
    {
        $cache = [];
        $transformer = $this->noEffectTransformer();

        $monsters = new Collection(
            Monster::where('only_for_location_type', LocationType::CAVE_OF_MEMORIES->value)->get(),
            $transformer
        );

        $locations = Location::where('type', LocationType::CAVE_OF_MEMORIES->value)->get();

        foreach ($locations as $location) {
            $cache['location-'.$location->id] = $this->manager->createData($monsters)->toArray();
        }

        return $cache;
    }

    /**
     * Build the query for the regular, non-exempt persisted Monster population for a source Game Map.
     */
    private function regularMonsterQuery(int $gameMapId): Builder
    {
        return Monster::where('is_celestial_entity', false)
            ->where('is_raid_monster', false)
            ->where('is_raid_boss', false)
            ->whereNull('only_for_location_type')
            ->where('game_map_id', $gameMapId);
    }

    /**
     * Configure the shared Monster transformer with the resolved Gem effects for the actual current Game Map.
     */
    private function transformerForGameMap(GameMap $gameMap): MonsterTransformer
    {
        return $this->monsterTransformer->withAreaGemEffects(
            $this->areaGemEffectService->resolveForGameMap($gameMap)
        );
    }

    /**
     * Configure the shared Monster transformer with no Gem effects, for Gem-neutral Monster classes.
     */
    private function noEffectTransformer(): MonsterTransformer
    {
        return $this->monsterTransformer->withAreaGemEffects(ResolvedAreaGemEffects::none());
    }

    /**
     * Build regular and easier Monster tiers for an event Game Map.
     */
    private function createMonstersForEventMaps(GameMap $gameMap, Collection $monsters): array
    {
        $surface = GameMap::where('default', true)->first();

        $easierMonsters = new Collection(
            $this->regularMonsterQuery($surface->id)->get(),
            $this->transformerForGameMap($gameMap)
        );

        return [
            'regular' => $this->manager->createData($monsters)->toArray(),
            'easier' => $this->manager->createData($easierMonsters)->toArray(),
        ];
    }
}
