<?php

namespace App\Game\Battle\Services;

use App\Flare\Models\Character;
use App\Flare\ServerFight\Pvp\PvpAttack;

class PvpService {

    private $pvpAttack;

    public function __construct(PvpAttack $pvpAttack) {
        $this->pvpAttack = $pvpAttack;
    }

    public function isDefenderAtPlayersLocation(Character $attacker, Character $defender) {
        $attackerMap = $attacker->map;
        $defenderMap = $defender->map;

        $xPositionMatches = $attackerMap->character_position_x === $defenderMap->character_position_x;
        $yPositionMatches = $attackerMap->character_position_y === $defenderMap->character_position_y;
        $samePlane        = $attackerMap->game_map_id          === $defenderMap->game_map_id;

        return $xPositionMatches && $yPositionMatches && $samePlane && $defender->currentAutomations->isEmpty();
    }

    public function getHealthObject(Character $attacker, Character $defender) {
        return [
            'attacker_health' => $this->pvpAttack->cache()->getCachedCharacterData($attacker, 'health'),
            'defender_health' => $this->pvpAttack->cache()->getCachedCharacterData($defender, 'health'),
        ];
    }

    public function attack(Character $attacker, Character $defender) {
        $this->pvpAttack->setUpPvpFight($attacker, $defender);
    }
}
