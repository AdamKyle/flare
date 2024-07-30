<?php

namespace App\Flare\ServerFight\Pvp;

use App\Flare\Models\Character;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;

class PvpBase {

    private CharacterCacheData $characterCacheData;

    private Character $attacker;

    protected int $attackerHealth;

    protected int $defenderHealth;

    public function __construct(CharacterCacheData $characterCacheData) {
        $this->characterCacheData = $characterCacheData;
    }

    public function cache(): CharacterCacheData {
        return $this->characterCacheData;
    }

    public function setAttacker(Character $attacker): void {
        $this->attacker = $attacker;
    }

    public function setDefender(Character $character): void {
        $this->defender = $character;
    }

    public function setAttackerHealth(int $attackerHealth = null): void {
        if (!is_null($attackerHealth)) {
            $this->attackerHealth = $attackerHealth;

            return;
        }

        $this->attackerHealth = $this->characterCacheData->getCachedCharacterData($this->attacker);
    }

    public function setDefenderHealth(int $defenderHealth = null): void {
        if (!is_null($defenderHealth)) {
            $this->defenderHealth = $defenderHealth;

            return;
        }

        $this->defenderHealth = $this->characterCacheData->getCachedCharacterData($this->defender);
    }

    public function getAttackerHealth(): int {
        return $this->attackerHealth;
    }

    public function getDefenderHealth(): int {
        return $this->defenderHealth;
    }
}
