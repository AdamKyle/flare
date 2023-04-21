<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Fight\Affixes;
use App\Flare\ServerFight\Monster\ServerMonster;

class PlayerHealing extends BattleBase {

    private Affixes $affixes;

    public function __construct(CharacterCacheData $characterCacheData, Affixes $affixes) {
        parent::__construct($characterCacheData);

        $this->affixes = $affixes;
    }

    public function healingPhase(Character $character, ServerMonster $monster, array $attackType, bool $isVoided) {
        if ($this->characterHealth <= 0) {
            if ($this->ressurect($character, $attackType)) {

                if (!$isVoided) {
                    $this->lifeSteal($character, $attackType);
                }

                return;
            }
        }

        if (!$isVoided) {
            $this->lifeSteal($character, $attackType);
        }
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

    protected function lifeSteal(Character $character, array $attackType) {
        $damage = $this->affixes->getAffixLifeSteal($character, $attackType, $this->monsterHealth);

        $this->monsterHealth -= $damage;
        $this->characterHealth += $damage;

        $this->mergeMessages($this->affixes->getMessages());

        $this->affixes->clearMessages();
    }
}
