<?php

namespace App\Flare\Builders\Character;

use Cache;
use App\Flare\Models\Character;

class CharacterPvpCacheData {

    public function setPvpData(Character $attacker, Character $defender, int $attackerHealth, int $defenderHealth) {
        Cache::put('pvp-cache-' . $attacker->id, $attackerHealth);
        Cache::put('pvp-cache-' . $defender->id, $defenderHealth);
    }

    public function removeFromPvpCache(Character $character) {
        Cache::delete('pvp-cache-' . $character->id);
    }

    public function fetchPvpCacheObject(Character $attacker, Character $defender) {
        $attacker = Cache::get('pvp-cache-' . $attacker->id);
        $defender = Cache::get('pvp-cache-' . $defender->id);

        if (is_null($attacker) || is_null($defender)) {
            return null;
        }

        return [
            'attacker_health' => $attacker,
            'defender_health' => $defender
        ];

    }

    public function removePlayerFromPvpCache(Character $character) {
        Cache::delete('pvp-cache-' . $character->id);
    }

    public function updatePlayerHealth(Character $character, int $health) {
        Cache::put('pvp-cache-' . $character->id, $health);
    }

    public function pvpCacheExists(Character $attacker, Character $defender): bool {
        return Cache::has('pvp-cache-' . $attacker->id) && Cache::has('pvp-cache-' . $defender->id);
    }
}
