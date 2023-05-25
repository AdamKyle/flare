<?php

namespace App\Game\Battle\Services\Concerns;

use Illuminate\Support\Facades\Cache;
use App\Flare\ServerFight\Monster\ServerMonster;

trait HandleCachedRaidCritterHealth {

    /**
     * Do we have cached health
     *
     * @param integer $characterId
     * @param integer $monsterId
     * @return boolean
     */
    public function hasCachedHealth(int $characterId, int $monsterId): bool {
        $cacheName = 'character-' . $characterId . '-raid-monster-' . $monsterId;

        return !is_null(Cache::get($cacheName));
    }

    /**
     * Get the cached health for the raid monster.
     *
     * @param integer $characterId
     * @param integer $monsterId
     * @return integer|null
     */
    public function getCachedHealth(int $characterId, int $monsterId): ?int {
        $cacheName = 'character-' . $characterId . '-raid-monster-' . $monsterId;

        $cache = Cache::get($cacheName);

        if (is_null($cache)) {
            return null;
        }

        return $cache['monster_current_health'];
    }

    /**
     * Get the cached server monster from the previous attack.
     *
     * @param integer $characterId
     * @param integer $monsterId
     * @return array|null
     */
    public function getCachedMonster(int $characterId, int $monsterId): ?array {
        $cacheName = 'character-' . $characterId . '-raid-monster-' . $monsterId;

        $cache = Cache::get($cacheName);

        if (is_null($cache)) {
            return null;
        }

        return $cache['server_monster'];
    }

    /**
     * Get cached fight data.
     *
     * @param integer $characterId
     * @param integer $monsterId
     * @return array|null
     */
    public function getCachedFightData(int $characterId, int $monsterId): ?array {
        $cacheName = 'character-' . $characterId . '-raid-monster-' . $monsterId;

        $cache = Cache::get($cacheName);

        if (is_null($cache)) {
            return null;
        }

        return $cache['fight_data'];
    }

    /**
     * Set the cached health for the monster.
     *
     * @param integer $characterId
     * @param integer $monsterId
     * @param integer $health
     * @return void
     */
    public function setCachedHealth(ServerMonster $serverMonster, array $fightData, int $characterId, int $monsterId, int $health) {
        $cacheName = 'character-' . $characterId . '-raid-monster-' . $monsterId;

        Cache::put($cacheName, [
            'monster_current_health' => $health,
            'server_monster'         => $serverMonster->getMonster(),
            'fight_data'             => $fightData,
        ], now()->addMinutes(20));
    }

    /**
     * Delete the monsters cached health amount.
     *
     * @param integer $characterId
     * @param integer $monsterId
     * @return void
     */
    public function deleteMonsterCacheHealth(int $characterId, int $monsterId) {
        Cache::delete('character-' . $characterId . '-raid-monster-' . $monsterId);
    }
}