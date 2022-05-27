<?php

namespace App\Flare\ServerFight;

use App\Flare\Builders\Character\CharacterCacheData;

class BattleBase extends BattleMessages {

    protected int $characterHealth;

    protected int $monsterHealth;

    protected CharacterCacheData $characterCacheData;

    public function __construct(CharacterCacheData $characterCacheData)
    {
        parent::__construct();

        $this->characterCacheData = $characterCacheData;
    }

    public function setCharacterHealth(int $characterHealth)
    {
        $this->characterHealth = $characterHealth;
    }

    public function setMonsterHealth(int $monsterHealth)
    {
        $this->monsterHealth = $monsterHealth;
    }

    public function getCharacterHealth(): int
    {
        return $this->characterHealth;
    }

    public function getMonsterHealth(): int
    {
        return $this->monsterHealth;
    }



}
