<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Fight\Affixes;
use App\Flare\ServerFight\Monster\ServerMonster;

class PlayerHealing extends BattleBase {

    private CharacterCacheData $characterCacheData;

    private Affixes $affixes;

    private int $characterHealth;

    private int $monsterHealth;

    public function __construct(CharacterCacheData $characterCacheData, Affixes $affixes) {
        parent::__construct();

        $this->characterCacheData = $characterCacheData;
        $this->affixes            = $affixes;
    }

    public function setCharacterHealth(int $characterHealth): PlayerHealing {
        $this->characterHealth = $characterHealth;

        return $this;
    }

    public function setMonsterHealth(int $monsterHealth): PlayerHealing {
        $this->monsterHealth = $monsterHealth;

        return $this;
    }

    public function getMonsterHealth(): int {
        return $this->monsterHealth;
    }

    public function getCharacterHealth(): int {
        return $this->characterHealth;
    }

    public function healingPhase(Character $character, ServerMonster $monster, array $attackType, bool $isVoided) {
        if ($this->characterHealth <= 0) {
            if ($this->ressurect($character, $attackType)) {
                $this->lifeSteal($character, $monster, $attackType);

                return;
            }
        }

        $this->lifeSteal($character, $monster, $attackType);
    }

    protected function ressurect(Character $character, array $attackType): bool {
        $chance = $attackType['res_chance'];

        if (rand(1, 100) > (100 - 100 * $chance)) {
            $this->addMessage('You are pulled back from the void and given one health!', 'player-action');

            $this->characterHealth = 1;

            return true;
        }

        return false;
    }

    protected function lifeSteal(Character $character, ServerMonster $monster, array $arrayType) {
        $damage = $this->affixes->getAffixLifeSteal($character, $monster, $attackType);

        $this->monsterHealth -= $damage;
        $this->characterHealth += $damage;

        $this->mergeMessages($this->affixes->getMessages());

        $this->affixes->clearMessages();
    }
}
