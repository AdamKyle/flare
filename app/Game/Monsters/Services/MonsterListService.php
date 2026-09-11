<?php

namespace App\Game\Monsters\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Maps\Values\LocationType;
use App\Game\Monsters\Values\MonsterCacheKey;
use Illuminate\Support\Facades\Cache;

class MonsterListService
{
    use ResponseBuilder;

    public function __construct(
        private readonly BuildMonsterCacheService $buildMonsterCacheService,
        private readonly CharacterGemMonsterCacheService $characterGemMonsterCacheService,
    ) {}

    /**
     * Build the Monster list payload for the Character's current context.
     */
    public function getMonstersForCharacter(Character $character): array
    {
        $monsters = $this->resolveMonsterDataSetForCharacter($character);

        $payload = $this->buildPayload($monsters);

        return $this->successResult($payload);
    }

    /**
     * Return the Monster list for the Character's current context.
     */
    public function getMonstersForCharacterAsList(Character $character): array
    {
        $monsters = $this->resolveMonsterDataSetForCharacter($character);

        return $this->buildPayload($monsters);
    }

    /**
     * Return the selected Monster from the Character's current context.
     */
    public function getMonsterForFight(Character $character, int $monsterId): ?array
    {
        $monsters = $this->resolveMonsterDataSetForCharacter($character)['data'] ?? [];

        return collect($monsters)->where('id', $monsterId)->first();
    }

    /**
     * Resolve the Monster dataset for the Character's current Map and Location context.
     */
    public function resolveMonsterDataSetForCharacter(Character $character): array
    {
        $characterMap = $character->map;
        $gameMap = $characterMap->gameMap;

        $this->ensureMonsterCache();

        $currentLocation = $this->findCurrentLocation(
            $characterMap->character_position_x,
            $characterMap->character_position_y,
            $characterMap->game_map_id
        );

        $weeklyMonsters = $this->resolveWeeklyMonsters($currentLocation);

        if (! is_null($weeklyMonsters)) {
            return $weeklyMonsters;
        }

        $sharedDataset = $this->resolveSharedDataset($character, $gameMap, $currentLocation);

        return $this->characterGemMonsterCacheService->resolveForCharacter($character, $sharedDataset);
    }

    /**
     * Resolve the Character-neutral shared Monster dataset for the current
     * Location/Map context, before any Character-specific Gem progression overlay.
     */
    private function resolveSharedDataset(Character $character, GameMap $gameMap, ?Location $currentLocation): array
    {
        $locationMonsters = $this->resolveLocationGemMonsters($currentLocation);

        if (! is_null($locationMonsters)) {
            return $locationMonsters;
        }

        $monstersKey = $gameMap->name;
        $monsters = $this->baseMonsters($monstersKey);

        return $this->applyMapTierOverrides(
            $monsters,
            $monstersKey,
            $gameMap->mapType()->isTheIcePlane(),
            $gameMap->mapType()->isDelusionalMemories(),
            $this->characterHasPurgatoryAccess($character)
        );
    }

    /**
     * Ensure the Monster caches required by the current context exist.
     */
    private function ensureMonsterCache(): void
    {
        if (! Cache::has(MonsterCacheKey::MONSTERS->value)) {
            $this->buildMonsterCacheService->buildCache();
        }

        if (! Cache::has(MonsterCacheKey::WEEKLY_MONSTERS->value)) {
            $this->buildMonsterCacheService->buildWeeklyFightCache();
        }

        if (! Cache::has(MonsterCacheKey::LOCATION_MONSTERS->value)) {
            $this->buildMonsterCacheService->buildLocationCache();
        }
    }

    /**
     * Find the Location at the supplied Map coordinates.
     */
    private function findCurrentLocation(int $x, int $y, int $gameMapId): ?Location
    {
        return Location::where('x', $x)
            ->where('y', $y)
            ->where('game_map_id', $gameMapId)
            ->first();
    }

    /**
     * Resolve the Weekly Fight Monster dataset for the current Location.
     */
    private function resolveWeeklyMonsters(?Location $currentLocation): ?array
    {
        if (is_null($currentLocation) || is_null($currentLocation->type)) {
            return null;
        }

        if (! LocationType::from($currentLocation->type)->isWeeklyFightLocationType()) {
            return null;
        }

        $weeklyCache = Cache::get(MonsterCacheKey::WEEKLY_MONSTERS->value);
        $dataset = $weeklyCache['location-type-'.$currentLocation->type] ?? null;

        if (is_null($dataset) || count($dataset['data'] ?? []) === 0) {
            return null;
        }

        return $dataset;
    }

    /**
     * Resolve the Location Gem Monster dataset for the current Location.
     */
    private function resolveLocationGemMonsters(?Location $currentLocation): ?array
    {
        if (is_null($currentLocation)) {
            return null;
        }

        $locationCache = Cache::get(MonsterCacheKey::LOCATION_MONSTERS->value);
        $dataset = $locationCache['location-'.$currentLocation->id] ?? null;

        if (is_null($dataset) || count($dataset['data'] ?? []) === 0) {
            return null;
        }

        return $dataset;
    }

    /**
     * Return the base Monster dataset for the Map cache key.
     */
    private function baseMonsters(string $monstersKey): array
    {
        $monstersCache = Cache::get(MonsterCacheKey::MONSTERS->value);

        return $monstersCache[$monstersKey] ?? ['data' => []];
    }

    /**
     * Apply the special-Map Monster tier override.
     */
    private function applyMapTierOverrides(
        array $current,
        string $monstersKey,
        bool $isTheIcePlane,
        bool $isDelusionalMemories,
        bool $hasPurgatoryAccess
    ): array {
        if (! $isTheIcePlane && ! $isDelusionalMemories) {
            return $current;
        }

        $monstersCache = Cache::get(MonsterCacheKey::MONSTERS->value);
        $tier = $hasPurgatoryAccess ? 'regular' : 'easier';

        return $monstersCache[$monstersKey][$tier] ?? $current;
    }

    /**
     * Convert the Monster dataset into the compact API list payload.
     */
    private function buildPayload(array $monsters): array
    {
        return collect($monsters['data'] ?? [])->map(function ($monster) {
            return [
                'id' => $monster['id'],
                'name' => $monster['name'],
                'max_level' => $monster['max_level'],
            ];
        })->values()->toArray();
    }

    /**
     * Determine whether the Character has Purgatory access.
     */
    private function characterHasPurgatoryAccess(Character $character): bool
    {
        $slots = optional($character->inventory)->slots;

        return $slots->where('item.effect', ItemEffectType::PURGATORY->value)->count() > 0;
    }
}
