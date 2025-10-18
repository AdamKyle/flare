<?php

namespace App\Game\Battle\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Flare\Services\BuildMonsterCacheService;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Core\Traits\ResponseBuilder;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Get monsters for the character's current position and map context.
 *
 * @param  Character  $character
 * @return array
 */
class MonsterListService
{
    use ResponseBuilder;

    /**
     * @throws InvalidArgumentException
     */
    public function getMonstersForCharacter(Character $character): array
    {
        $this->ensureMonsterCache();

        $characterMap = $character->map;

        $locationWithEffect = $this->findLocationWithEffect(
            $characterMap->character_position_x,
            $characterMap->character_position_y,
            $characterMap->game_map_id
        );

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

        $monsters = $this->applyLocationEffectOverrides(
            $monstersCache,
            $monsters,
            $locationWithEffect,
            $isTheIcePlane,
            $hasPurgatoryAccess,
            $monstersKey
        );

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

        $payload = $this->buildPayload($monsters);

        return $this->successResult($payload);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function ensureMonsterCache(): void
    {
        if (! Cache::has('monsters')) {
            resolve(BuildMonsterCacheService::class)->buildCache();
        }
    }

    private function findLocationWithEffect(int $x, int $y, int $gameMapId): ?Location
    {
        return Location::whereNotNull('enemy_strength_increase')
            ->where('x', $x)
            ->where('y', $y)
            ->where('game_map_id', $gameMapId)
            ->first();
    }

    private function findLocationWithType(int $x, int $y, int $gameMapId): ?Location
    {
        return Location::whereNotNull('type')
            ->where('x', $x)
            ->where('y', $y)
            ->where('game_map_id', $gameMapId)
            ->first();
    }

    private function baseMonsters(array $monstersCache, string $monstersKey): array
    {
        return $monstersCache[$monstersKey] ?? ['data' => []];
    }

    private function applyLocationEffectOverrides(
        array $monstersCache,
        array $current,
        ?Location $locationWithEffect,
        bool $isTheIcePlane,
        bool $hasPurgatoryAccess,
        string $monstersKey
    ): array {
        if (! is_null($locationWithEffect) && ! $isTheIcePlane) {
            $current = $monstersCache[$locationWithEffect->name] ?? $current;
        } elseif (! is_null($locationWithEffect) && $isTheIcePlane) {

            if ($hasPurgatoryAccess) {
                $current = $monstersCache[$locationWithEffect->name] ?? $current;
            } else {
                $current = $monstersCache[$monstersKey]['easier'] ?? $current;
            }
        }

        return $current;
    }

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

    private function applySpecialLocationOverride(
        array $current,
        ?Location $locationWithType
    ): array {
        if (! is_null($locationWithType)) {
            $monstersForLocation = Cache::get('special-location-monsters');

            if (isset($monstersForLocation['location-type-'.$locationWithType->type])) {
                $current = $monstersForLocation['location-type-'.$locationWithType->type];
            }
        }

        return $current;
    }

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

    private function characterHasPurgatoryAccess(Character $character): bool
    {
        $slots = optional($character->inventory)->slots;

        if (is_null($slots)) {
            return false;
        }

        return $slots->where('item.effect', ItemEffectsValue::PURGATORY)->count() > 0;
    }
}
