<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Fight\Affixes;
use App\Flare\ServerFight\Fight\CharacterAttacks\Types\CastType;
use App\Flare\ServerFight\Monster\ServerMonster;
use App\Flare\Values\AttackTypeValue;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;

class PlayerHealing extends BattleBase {

    private Affixes $affixes;

    private CastType $castType;

    public function __construct(CharacterCacheData $characterCacheData, Affixes $affixes, CastType $castType) {
        parent::__construct($characterCacheData);

        $this->affixes = $affixes;
        $this->castType = $castType;
    }

    public function resurrect(array $attackType): bool {
        $chance = $attackType['res_chance'];

        if (rand(1, 100) > (100 - 100 * $chance)) {
            $this->addMessage('You are pulled back from the void and given one health!', 'player-action');

            $this->characterHealth = 1;

            return true;
        }

        return false;
    }

    public function healInBattle(Character $character, array $attackType) {
        $this->castType->setMonsterHealth($this->monsterHealth);
        $this->castType->setCharacterHealth($this->characterHealth);
        $this->castType->setCharacterAttackData($character, $this->isVoided, AttackTypeValue::ATTACK);

        $this->castType->healDuringFight($character);

        $this->monsterHealth = $this->castType->getMonsterHealth();
        $this->characterHealth = $this->castType->getCharacterHealth();

        $this->mergeMessages($this->castType->getMessages());

        $this->castType->clearMessages();
    }

    protected function lifeSteal(Character $character, array $attackType) {
        $damage = $this->affixes->getAffixLifeSteal($character, $attackType, $this->monsterHealth);

        $this->monsterHealth -= $damage;
        $this->characterHealth += $damage;

        $this->mergeMessages($this->affixes->getMessages());

        $this->affixes->clearMessages();
    }
}
