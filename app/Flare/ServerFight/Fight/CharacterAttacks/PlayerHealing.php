<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Fight\Affixes;
use App\Flare\ServerFight\Fight\CharacterAttacks\Types\CastType;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;

class PlayerHealing extends BattleBase
{
    private Affixes $affixes;

    private CastType $castType;

    public function __construct(CharacterCacheData $characterCacheData, Affixes $affixes, CastType $castType)
    {
        parent::__construct($characterCacheData);

        $this->affixes = $affixes;
        $this->castType = $castType;
    }

    public function resurrect(array $attackType): bool
    {
        $chance = $attackType['res_chance'];

        if (rand(1, 100) > (100 - 100 * $chance)) {
            $this->addMessage('You are pulled back from the void and given one health!', 'player-action');

            $this->characterHealth = 1;

            return true;
        }

        return false;
    }

    public function healInBattle(Character $character, array $attackType)
    {
        $this->castType->setMonsterHealth($this->monsterHealth);
        $this->castType->setCharacterHealth($this->characterHealth);
        $this->castType->setCharacterAttackData($character, $this->isVoided, $attackType['attack_type']);

        $this->castType->healDuringFight($character);

        $this->monsterHealth = $this->castType->getMonsterHealth();
        $this->characterHealth = $this->castType->getCharacterHealth();

        $this->mergeMessages($this->castType->getMessages());

        $this->castType->clearMessages();
    }

    public function lifeSteal(Character $character, bool $isPvp = false)
    {

        if ($character->classType()->isVampire()) {

            $damage = $this->characterCacheData->getCachedCharacterData($character, 'dur_modded') * 0.05;

            $this->monsterHealth -= $damage;
            $this->characterHealth += $damage;

            if ($isPvp) {
                $this->addAttackerMessage('You lash out in rage and grip the enemies neck. Take what you need child! You deal and heal for: '.number_format($damage), 'player-action');
                $this->addDefenderMessage('The enemy feels the pain of your attack, alas they need your valuable blood to survive! You take: '.number_format($damage).' damage.', 'enemy-action');
            } else {
                $this->addMessage('You lash out in rage and grip the enemies neck. Take what you need child! You deal and heal for: '.number_format($damage), 'player-action');
            }
        }
    }
}
