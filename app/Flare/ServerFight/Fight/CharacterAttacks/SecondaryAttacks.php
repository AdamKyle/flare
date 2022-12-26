<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Fight\Affixes;
use App\Flare\ServerFight\Monster\ServerMonster;

class SecondaryAttacks extends BattleBase {

    private bool $isCharacterVoided = false;

    private Affixes $affixes;

    public function __construct(CharacterCacheData $characterCacheData, Affixes $affixes) {
        parent::__construct($characterCacheData);

        $this->affixes = $affixes;
    }

    public function setAttackData(array $attackData) {
        $this->attackData = $attackData;
    }

    public function setIsCharacterVoided(bool $voided) {
        $this->isVoided = $voided;
    }

    public function setIsEnemyEntranced(bool $entranced) {
        $this->isEnemyEntranced = $entranced;
    }

    public function doSecondaryAttack(Character $character, ServerMonster $monster = null, float $affixReduction = 0.0, bool $isPvp = false) {
        if (!$this->isVoided) {

            if ($this->isEnemyEntranced) {
                $affixReduction = 0.0;
            }
            
            $this->classSpecialtyDamage($isPvp);

            $this->affixLifeStealingDamage($character, $monster, $affixReduction, $isPvp);

            $this->affixDamage($character, $monster, $affixReduction, $isPvp);

            $this->ringDamage($isPvp);

        } else {
            if ($isPvp) {
                $this->addAttackerMessage('You are voided, none of your rings or enchantments fire ...', 'enemy-action');
            } else {
                $this->addMessage('You are voided, none of your rings or enchantments fire ...', 'enemy-action');
            }
        }
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
            $this->addDefenderMessage('You manage resist the (damaging, resistible/non resistible) enchantment damage coming in to: ' . number_format($damage), 'regular');
        }

        if ($damage > 0) {
            $this->monsterHealth -= $damage;
        }

        $this->affixes->clearMessages();
    }

    public function classSpecialtyDamage(bool $isPvp = false) {
        $special = $this->attackData['special_damage'];

        if (empty($special)) {
            return;
        }

        if ($special['required_attack_type'] === $this->attackData['attack_type']) {
            $this->monsterHealth -= $special['damage'];

            $this->addMessage('Your class special: ' . $special['name'] . ' fires off and you do: ' . number_format($special['damage']) . ' damage to the enemy!', "player-action", $isPvp);

            if ($isPvp) {
                $this->addDefenderMessage('The enemy lashes out using one of their coveted skills (class special) to do:  ' . number_format($special['damage']) . ' damage.', 'enemy-action');
            }
        }
    }

    public function affixLifeStealingDamage(Character $character, ServerMonster $monster = null, float $affixDamageReduction = 0.0, bool $isPvp = false) {

        if ($this->monsterHealth <= 0) {
            return;
        }

        $resistance = 0.0;

        if (!is_null($monster) && !$this->isEnemyEntranced) {
            $resistance = $monster->getMonsterStat('affix_resistance');
        }

        if ($this->isEnemyEntranced) {
            $this->affixes->setEntranced();
        }

        $lifeStealingDamage = $this->affixes->getAffixLifeSteal($character, $this->attackData, $this->monsterHealth, $resistance, $isPvp);

        if (!$isPvp) {
            $this->mergeMessages($this->affixes->getMessages());
        } else {
            $this->mergeAttackerMessages($this->affixes->getAttackerMessages());
            $this->mergeDefenderMessages($this->affixes->getDefenderMessages());
        }

        $this->affixes->clearMessages();

        if ($isPvp && $affixDamageReduction > 0.0) {
            $lifeStealingDamage = $lifeStealingDamage - $lifeStealingDamage * $affixDamageReduction;

            $this->addAttackerMessage('The enemy reduced your life stealing enchantments damage to: ' . number_format($lifeStealingDamage), 'enemy-action');
            $this->addDefenderMessage('You manage, by the skin of your teeth, to use the last of your magics to reduce their life stealing (enchantment) damage to: ' . number_format($lifeStealingDamage), 'regular');
        }

        if ($lifeStealingDamage > 0) {
            $this->monsterHealth   -= $lifeStealingDamage;
            $this->characterHealth += $lifeStealingDamage;

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
