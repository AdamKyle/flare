<?php

namespace App\Flare\Builders\Character;

use Cache;
use App\Flare\Models\Character;

class CharacterPvpCacheData {

    public function setPvpData(Character $attacker, Character $defender, int $attackerHealth, int $defenderHealth) {
        $pvpCache = Cache::get('pvp-cache');

        if (is_null($pvpCache)) {
            $pvpCache = [];

            $pvpCache[] = [
                'attacker_id'      => $attacker->id,
                'defender_id'      => $defender->id,
                'attacker_health'  => $attackerHealth,
                'defender_health'  => $defenderHealth,
            ];

            Cache::put('pvp-cache', $pvpCache);

            return;
        }

        foreach ($pvpCache as $index => $cache) {
            if ($cache['attacker_id'] === $attacker->id && $cache['defender_id'] === $defender->id) {
                $pvpCache[$index] = [
                    'attacker_id'     => $attacker->id,
                    'defender_id'     => $defender->id,
                    'attacker_health' => $attackerHealth,
                    'defender_health' => $defenderHealth,
                ];
            }

            if ($cache['defender_id'] === $attacker->id && $cache['attacker_id'] === $defender->id) {
                $pvpCache[$index] = [
                    'attacker_id'     => $defender->id,
                    'defender_id'     => $attacker->id,
                    'attacker_health' => $defenderHealth,
                    'defender_health' => $attacker,
                ];
            }
        }

        Cache::put('pvp-cache', $pvpCache);
    }

    public function removeFromPvpCache(Character $character) {
        $pvpCache = Cache::get('pvp-cache');

        if (is_null($pvpCache)) {
            return;
        }

        foreach ($pvpCache as $index => $cache) {
            if ($cache['attacker_id'] === $character->id) {
                unset($cache[$index]);
            }

            if ($cache['defender_id'] === $character->id) {
                unset($cache[$index]);
            }
        }

        Cache::put('pvp-cache', $pvpCache);
    }

    public function fetchPvpCacheObject(Character $attacker, Character $defender) {
        $pvpCache = Cache::get('pvp-cache');

        if (is_null($pvpCache)) {
            return null;
        }

        foreach ($pvpCache as $index => $cache) {
            if ($cache['attacker_id'] === $attacker->id && $cache['defender_id'] === $defender->id) {
                return $pvpCache[$index];
            }

            if ($cache['defender_id'] === $attacker->id && $cache['attacker_id'] === $defender->id) {
                $cache = $pvpCache[$index];

                return [
                    'attacker_id'      => $attacker->id,
                    'defender_id'      => $defender->id,
                    'attacker_health'  => $cache['defender_health'],
                    'defender_health'  => $cache['attacker_health'],
                ];
            }
        }
    }

    public function removePlayerFromPvpCache(Character $character) {
        $pvpCache = Cache::get('pvp-cache');

        if (is_null($pvpCache)) {
            return;
        }

        foreach ($pvpCache as $index => $cache) {
            if ($cache['attacker_id'] === $character->id) {
                unset($pvpCache[$index]);
            }

            if ($cache['defender_id'] === $character->id) {
                unset($pvpCache[$index]);
            }
        }

        Cache::put('pvp-cache', $pvpCache);
    }

    public function updatePlayerHealth(Character $character, int $health) {
        $pvpCache = Cache::get('pvp-cache');

        if (is_null($pvpCache)) {
            return;
        }

        foreach ($pvpCache as $index => $cache) {
            if ($cache['attacker_id'] === $character->id) {
                $pvpCache[$index] = [
                    'attacker_id'      => $character->id,
                    'defender_id'      => $cache['defender_id'],
                    'attacker_health'  => $health,
                    'defender_health'  => $cache['defender_health'],
                ];
            }

            if ($cache['defender_id'] === $character->id) {
                $pvpCache[$index] = [
                    'attacker_id'      => $cache['attacker_id'],
                    'defender_id'      => $character->id,
                    'attacker_health'  => $cache['attacker_health'],
                    'defender_health'  => $health,
                ];
            }
        }

        Cache::put('pvp-cache', $pvpCache);
    }

    public function pvpCacheExists(Character $attacker, Character $defender): bool {
        $pvpCache = Cache::get('pvp-cache');

        if (is_null($pvpCache)) {
            return false;
        }

        foreach ($pvpCache as $cache) {
            if ($cache['attacker_id'] === $attacker->id && $cache['defender_id'] === $defender->id) {
                return true;
            }

            if ($cache['defender_id'] === $attacker->id && $cache['attacker_id'] === $defender->id) {
                return true;
            }
        }

        return false;
    }
}
