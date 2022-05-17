<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\Types;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Fight\Entrance;
use App\Flare\ServerFight\Monster\ServerMonster;

class WeaponType extends BattleBase {

    private int $monsterHealth;

    private int $characterHealth;

    private array $attackData;

    private CharacterCacheData $characterCacheData;

    private Entrance $entrance;

    public function __construct(CharacterCacheData $characterCacheData, Entrance$entrance) {
        parent::__construct();

        $this->characterCacheData = $characterCacheData;
        $this->entrance           = $entrance;
    }

    public function setMonsterHealth(int $monsterHealth): WeaponType {
        $this->monsterHealth = $monsterHealth;

        return $this;
    }

    public function setCharacterHealth(int $characterHealth): WeaponType {
        $this->characterHealth = $characterHealth;

        return $this;
    }

    public function setCharacterAttackData(Character $character, bool $isVoided): WeaponType {

        $this->attackData = $this->characterCacheData->getDataFromAttackCache($character, $isVoided ? 'voided_attack' : 'attack');

        return $this;
    }

    public function doWeaponAttack(Character $character, ServerMonster $serverMonster): WeaponType {

        $this->entrance->playerEntrance($character, $serverMonster, $this->attackData);

        $this->mergeMessages($this->entrance->getMessages());

        if ($this->entrance->isEnemyEntranced()) {

        }

        return $this;
    }

    public function resetMessages() {
        $this->clearMessages();
        $this->entrance->clearMessages();
    }

    public function getMonsterHealth() {
        return $this->monsterHealth;
    }

    public function getCharacterHealth() {
        return $this->characterHealth;
    }
}
