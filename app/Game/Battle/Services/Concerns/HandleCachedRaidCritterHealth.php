<?php

namespace App\Game\Battle\Services\Concerns;

use App\Flare\ServerFight\Monster\ServerMonster;
use Illuminate\Support\Facades\Cache;

trait HandleCachedRaidCritterHealth
{
    /**
     * Do we have cached health
     */
    public function hasCachedHealth(int $characterId, int $monsterId): bool
    {
        $cacheName = 'character-'.$characterId.'-raid-monster-'.$monsterId;

        return ! is_null(Cache::get($cacheName));
    }

    /**
     * Get the cached health for the raid monster.
     */
    public function getCachedHealth(int $characterId, int $monsterId): ?int
    {
        $cacheName = 'character-'.$characterId.'-raid-monster-'.$monsterId;

        $cache = Cache::get($cacheName);

        if (is_null($cache)) {
            return null;
        }

        return $cache['monster_current_health'];
    }

    /**
     * Get the cached server monster from the previous attack.
     */
    public function getCachedMonster(int $characterId, int $monsterId): ?array
    {
        $cacheName = 'character-'.$characterId.'-raid-monster-'.$monsterId;

        $cache = Cache::get($cacheName);

        if (is_null($cache)) {
            return null;
        }

        return $cache['server_monster'];
    }

    /**
     * Get cached fight data.
     */
    public function getCachedFightData(int $characterId, int $monsterId): ?array
    {
        $cacheName = 'character-'.$characterId.'-raid-monster-'.$monsterId;

        $cache = Cache::get($cacheName);

        if (is_null($cache)) {
            return null;
        }

        return $cache['fight_data'];
    }

    /**
     * Set the cached health for the monster.
     *
     * @return void
     */
    public function setCachedHealth(ServerMonster $serverMonster, array $fightData, int $characterId, int $monsterId, int $health)
    {
        $cacheName = 'character-'.$characterId.'-raid-monster-'.$monsterId;

        Cache::put($cacheName, [
            'monster_current_health' => $health,
            'server_monster' => $serverMonster->getMonster(),
            'fight_data' => $fightData,
        ], now()->addMinutes(20));
    }

    /**
     * Delete the monsters cached health amount.
     *
     * @return void
     */
    public function deleteMonsterCacheHealth(int $characterId, int $monsterId)
    {
        Cache::delete('character-'.$characterId.'-raid-monster-'.$monsterId);
    }
}
