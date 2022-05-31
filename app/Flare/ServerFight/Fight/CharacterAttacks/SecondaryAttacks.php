<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Fight\Affixes;
use App\Flare\ServerFight\Monster\ServerMonster;

class SecondaryAttacks extends BattleBase {

    private array $attackData;

    private Affixes $affixes;

    public function __construct(CharacterCacheData $characterCacheData, Affixes $affixes) {
        parent::__construct($characterCacheData);

        $this->affixes = $affixes;
    }

    public function setAttackData(array $attackData) {
        $this->attackData = $attackData;
    }

    public function affixDamage(Character $character, ServerMonster $monster = null, float $defenderDamageReduction = 0.0, bool $isPvp = false) {

        $resistance = 0.0;

        if (!is_null($monster)) {
            $resistance = $monster->getMonsterStat('affix_resistance');
        }

        $damage = $this->affixes->getCharacterAffixDamage($character, $resistance, $this->attackData, $isPvp);

        if (!$isPvp) {
            $this->mergeMessages($this->affixes->getMessages());
        } else {
            $this->mergeAttackerMessages($this->affixes->getAttackerMessages());
            $this->mergeDefenderMessages($this->affixes->getDefenderMessages());
        }

        if ($isPvp) {
            $damage = $damage - $damage * $defenderDamageReduction;

            $this->addAttackerMessage('The enemy is able to reduce the damage of your (damaging, resistible/non resistible) enchantment damage to: ' . number_format($damage), 'enemy-action');
            $this->addDefenderMessage('You manage to scour up some strength and resist the (damaging, resistible/non resistible) enchantment damage coming in to: ' . number_format($damage), 'regular');
        }

        if ($damage > 0) {
            $this->monsterHealth -= $damage;
        }

        $this->affixes->clearMessages();
    }

    public function affixLifeStealingDamage(Character $character, ServerMonster $monster = null, float $affixDamageReduction = 0.0, bool $isPvp = false) {
        if ($this->monsterHealth <= 0) {
            return;
        }

        $resistance = 0.0;

        if (!is_null($monster)) {
            $resistance = $monster->getMonsterStat('affix_resistance');
        }

        $lifeStealing = $this->affixes->getAffixLifeSteal($character, $this->attackData, $resistance, $isPvp);

        if (!$isPvp) {
            $this->mergeMessages($this->affixes->getMessages());
        } else {
            $this->mergeAttackerMessages($this->affixes->getAttackerMessages());
            $this->mergeDefenderMessages($this->affixes->getDefenderMessages());
        }

        $this->affixes->clearMessages();

        $damage = $this->monsterHealth * $lifeStealing;

        if ($isPvp) {
            $damage = $damage - $damage * $affixDamageReduction;

            $this->addAttackerMessage('The enemy reduced your life stealing enchantments damage to: ' . number_format($damage), 'enemy-action');
            $this->addDefenderMessage('You manage, by the skin of your teeth, to use the last of your magics to reduce their life stealing (enchantment) damage to: ' . number_format($damage), 'regular');
        }

        if ($damage > 0) {
            $this->monsterHealth   -= $damage;
            $this->characterHealth += $damage;

            $maxCharacterHealth = $this->characterCacheData->getCachedCharacterData($character, 'health');

            if ($this->characterHealth >= $maxCharacterHealth) {
                $this->characterHealth = $maxCharacterHealth;
            }
        }
    }

    public function ringDamage(bool $ispvp = false) {
        $ringDamage = $this->attackData['ring_damage'];

        if ($ringDamage > 0) {
            $this->monsterHealth -= ($ringDamage - $ringDamage * $this->attackData['damage_deduction']);

            $this->addMessage('Your rings hit for: ' . number_format($ringDamage), 'player-action', $ispvp);

            if ($ispvp) {
                $this->addDefenderMessage('The enemies rings glow and lash out for: ' . number_format($ringDamage), 'enemy-action');
            }
        }
    }

}
