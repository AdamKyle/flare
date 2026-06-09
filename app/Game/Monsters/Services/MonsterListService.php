<?php

namespace App\Game\Monsters\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Core\Traits\ResponseBuilder;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;

class MonsterListService
{
    use ResponseBuilder;

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
    public function getMonsterForFight(Character $character, int $monsterId): array
    {
        $monsters = $this->resolveMonsterDataSetForCharacter($character)['data'];

        return collect($monsters)->where('id', $monsterId)->first();
    }

    /*
     * Resolve the full monsters dataset for the character based on map and location rules.
     *
     * @param Character $character
     * @throws InvalidArgumentException
     * @return array
     */
    public function resolveMonsterDataSetForCharacter(Character $character): array
    {
        $this->ensureMonsterCache();

        $characterMap = $character->map;

        $locationWithType = $this->findLocationWithType(
            $characterMap->character_position_x,
            $characterMap->character_position_y,
            $characterMap->game_map_id
        );

        $isTheIcePlane = $character->map->gameMap->mapType()->isTheIcePlane();

        $isDelusionalMemories = $character->map->gameMap->mapType()->isDelusionalMemories();

        $hasPurgatoryAccess = $this->characterHasPurgatoryAccess($character);

        $monstersCache = Cache::get('monsters');

        $monstersKey = $character->map->gameMap->name;

        $monsters = $this->baseMonsters($monstersCache, $monstersKey);

        $monsters = $this->applyMapTierOverrides(
            $monstersCache,
            $monsters,
            $isTheIcePlane,
            $isDelusionalMemories,
            $hasPurgatoryAccess,
            $monstersKey
        );

        $monsters = $this->applySpecialLocationOverride(
            $monsters,
            $locationWithType
        );

        return $this->applyLocationStrengthIncrease(
            $monsters,
            $characterMap->character_position_x,
            $characterMap->character_position_y,
            $characterMap->game_map_id
        );
    }

    /*
     * Ensure the base monster cache exists; build it if missing.
     *
     * @throws InvalidArgumentException
     * @return void
     */
    private function ensureMonsterCache(): void
    {
        if (! Cache::has('monsters')) {
            resolve(BuildMonsterCacheService::class)->buildCache();
        }
    }

    /*
     * Find a location at the given coordinates that has a special location type.
     *
     * @param int $x
     * @param int $y
     * @param int $gameMapId
     * @return Location|null
     */
    private function findLocationWithType(int $x, int $y, int $gameMapId): ?Location
    {
        return Location::whereNotNull('type')
            ->where('x', $x)
            ->where('y', $y)
            ->where('game_map_id', $gameMapId)
            ->first();
    }

    /*
     * Get the base monsters list for the given map key.
     *
     * @param array $monstersCache
     * @param string $monstersKey
     * @return array
     */
    private function baseMonsters(array $monstersCache, string $monstersKey): array
    {
        return $monstersCache[$monstersKey] ?? ['data' => []];
    }

    /*
     * Apply map-tier overrides (regular vs easier) for special maps and Purgatory access.
     *
     * @param array $monstersCache
     * @param array $current
     * @param bool $isTheIcePlane
     * @param bool $isDelusionalMemories
     * @param bool $hasPurgatoryAccess
     * @param string $monstersKey
     * @return array
     */
    private function applyMapTierOverrides(
        array $monstersCache,
        array $current,
        bool $isTheIcePlane,
        bool $isDelusionalMemories,
        bool $hasPurgatoryAccess,
        string $monstersKey
    ): array {
        if ($isTheIcePlane && $hasPurgatoryAccess) {
            $current = $monstersCache[$monstersKey]['regular'] ?? $current;
        } elseif ($isTheIcePlane && ! $hasPurgatoryAccess) {
            $current = $monstersCache[$monstersKey]['easier'] ?? $current;
        }

        if ($isDelusionalMemories && $hasPurgatoryAccess) {
            $current = $monstersCache[$monstersKey]['regular'] ?? $current;
        } elseif ($isDelusionalMemories && ! $hasPurgatoryAccess) {
            $current = $monstersCache[$monstersKey]['easier'] ?? $current;
        }

        return $current;
    }

    /*
     * If standing on a special location type, override with that location-type monster list.
     *
     * @param array $current
     * @param Location|null $locationWithType
     * @return array
     */
    private function applySpecialLocationOverride(
        array $current,
        ?Location $locationWithType
    ): array {
        if (! is_null($locationWithType)) {
            $monstersForLocation = Cache::get('special-location-monsters');

            $monstersForLocationType = [];

            if (isset($monstersForLocation['location-type-'.$locationWithType->type])) {

                $monstersForLocationType = $monstersForLocation['location-type-'.$locationWithType->type];
            }

            if (count($monstersForLocationType['data'] ?? []) > 0) {
                $current = $monstersForLocationType;
            }
        }

        return $current;
    }

    /*
     * Apply an absolute integer stat increase to all monsters based on a location's enemy_strength_increase.
     *
     * @param array $monsters
     * @param int $x
     * @param int $y
     * @param int $gameMapId
     * @return array
     */
    private function applyLocationStrengthIncrease(array $monsters, int $x, int $y, int $gameMapId): array
    {
        $location = Location::where('x', $x)
            ->where('y', $y)
            ->where('game_map_id', $gameMapId)
            ->whereNotNull('enemy_strength_increase')
            ->first();

        if (is_null($location)) {
            return $monsters;
        }

        $increase = (int) $location->enemy_strength_increase;

        if ($increase <= 0) {
            return $monsters;
        }

        $statFields = ['str', 'dur', 'dex', 'chr', 'int', 'agi', 'focus', 'ac', 'to_hit_base'];

        $monsters['data'] = array_map(function (array $monster) use ($increase, $statFields) {
            foreach ($statFields as $field) {
                if (array_key_exists($field, $monster)) {
                    $monster[$field] += $increase;
                }
            }

            return $monster;
        }, $monsters['data'] ?? []);

        return $monsters;
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

        return $slots->where('item.effect', ItemEffectsValue::PURGATORY)->count() > 0;
    }
}
