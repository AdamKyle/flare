<?php

namespace App\Game\Monsters\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Maps\Values\LocationType;
use App\Game\Monsters\Values\MonsterCacheKey;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;

class MonsterListService
{
    use ResponseBuilder;

    public function __construct(private readonly BuildMonsterCacheService $buildMonsterCacheService) {}

    /*
     * Build a simple list payload of monsters for the character's current context.
     *
     * @param Character $character
     * @throws InvalidArgumentException
     * @return array
     */
    public function getMonstersForCharacter(Character $character): array
    {
        $monsters = $this->resolveMonsterDataSetForCharacter($character);

        $payload = $this->buildPayload($monsters);

        return $this->successResult($payload);
    }

    /**
     * Get a straight list of monsters as an array
     */
    public function getMonstersForCharacterAsList(Character $character): array
    {
        $monsters = $this->resolveMonsterDataSetForCharacter($character);

        return $this->buildPayload($monsters);
    }

    /**
     * Get the monster the character should fight.
     */
    public function getMonsterForFight(Character $character, int $monsterId): ?array
    {
        $monsters = $this->resolveMonsterDataSetForCharacter($character)['data'] ?? [];

        return collect($monsters)->where('id', $monsterId)->first();
    }

    /**
     * Resolve the full Monster dataset for the Character's current Map/Location Gem context.
     *
     * The resolution order is: ensure required caches exist, prefer a Weekly Fight
     * Location's cache, otherwise a Gem-bearing Location's cache, otherwise the
     * normal current Game Map cache, preserving the existing event-map override.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException
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
     * Ensure the required regular/Location/Weekly Monster caches exist for the Character's current context.
     *
     * @throws InvalidArgumentException
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
     * Find the actual Location at the given coordinates, regardless of Location Type.
     */
    private function findCurrentLocation(int $x, int $y, int $gameMapId): ?Location
    {
        return Location::where('x', $x)
            ->where('y', $y)
            ->where('game_map_id', $gameMapId)
            ->first();
    }

    /**
     * Resolve the Weekly Fight Monster dataset for the current Location, when applicable.
     *
     * @return array<string, mixed>|null
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
     * Resolve the Location Gem-affected Monster dataset for the current Location, when it has a rolled Location Gem cache.
     *
     * @return array<string, mixed>|null
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

    /*
     * Get the base monsters list for the given map key.
     *
     * @param string $monstersKey
     * @return array
     */
    private function baseMonsters(string $monstersKey): array
    {
        $monstersCache = Cache::get(MonsterCacheKey::MONSTERS->value);

        return $monstersCache[$monstersKey] ?? ['data' => []];
    }

    /*
     * Apply map-tier overrides (regular vs easier) for special maps and Purgatory access.
     *
     * @param array $current
     * @param string $monstersKey
     * @param bool $isTheIcePlane
     * @param bool $isDelusionalMemories
     * @param bool $hasPurgatoryAccess
     * @return array
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

    /*
     * Convert a full monster dataset into a compact list payload for the API.
     *
     * @param array $monsters
     * @return array
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

    /*
     * Determine if the character has Purgatory access via equipped/held items.
     *
     * @param Character $character
     * @return bool
     */
    private function characterHasPurgatoryAccess(Character $character): bool
    {
        $slots = optional($character->inventory)->slots;

        return $slots->where('item.effect', ItemEffectType::PURGATORY->value)->count() > 0;
    }
}
