<?php

namespace App\Game\Battle\Services\Concerns;

use Illuminate\Support\Facades\Cache;
use App\Flare\ServerFight\Monster\ServerMonster;

trait HandleCachedRaidCritterHealth {

    /**
     * Get the cached health for the raid monster.
     *
     * @param ServerMonster $serverMonster
     * @param integer $characterId
     * @param integer $monsterId
     * @return integer
     */
    public function getCachedHealth(ServerMonster $serverMonster, int $characterId, int $monsterId): int {
        $cacheName = 'character-' . $characterId . '-raid-monster-' . $monsterId;

        $cache = Cache::get($cacheName);

        if (is_null($cache)) {
            Cache::put($cacheName, [
                'monster_current_health' => $serverMonster->getHealth(),
            ], now()->addMinutes(20));
        }

        $cache = Cache::get($cacheName);

        return $cache['monster_current_health'];
    }

    /**
     * Set the cached health for the monster.
     *
     * @param integer $characterId
     * @param integer $monsterId
     * @param integer $health
     * @return void
     */
    public function setCachedHealth(int $characterId, int $monsterId, int $health) {
        $cacheName = 'character-' . $characterId . '-raid-monster-' . $monsterId;

        Cache::put($cacheName, [
            'monster_current_health' => $health,
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