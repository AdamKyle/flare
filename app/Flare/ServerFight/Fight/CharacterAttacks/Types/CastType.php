<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\Types;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Fight\Affixes;
use App\Flare\ServerFight\Fight\CanHit;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;
use App\Flare\ServerFight\Fight\Entrance;
use App\Flare\ServerFight\Monster\ServerMonster;

class CastType extends BattleBase
{

    private int $monsterHealth;

    private int $characterHealth;

    private array $attackData;

    private bool $isVoided;

    private CharacterCacheData $characterCacheData;

    private Entrance $entrance;

    private CanHit $canHit;

    private Affixes $affixes;

    private SpecialAttacks $specialAttacks;

    public function __construct(CharacterCacheData $characterCacheData, Entrance $entrance, CanHit $canHit, Affixes $affixes, SpecialAttacks $specialAttacks)
    {
        parent::__construct();

        $this->characterCacheData = $characterCacheData;
        $this->entrance           = $entrance;
        $this->canHit             = $canHit;
        $this->affixes            = $affixes;
        $this->specialAttacks     = $specialAttacks;
    }

    public function setMonsterHealth(int $monsterHealth): CastType
    {
        $this->monsterHealth = $monsterHealth;

        return $this;
    }

    public function setCharacterHealth(int $characterHealth): CastType
    {
        $this->characterHealth = $characterHealth;

        return $this;
    }

    public function setCharacterAttackData(Character $character, bool $isVoided): CastType
    {

        $this->attackData = $this->characterCacheData->getDataFromAttackCache($character, $isVoided ? 'voided_cast' : 'cast');
        $this->isVoided = $isVoided;

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

    public function castAttack(Character $character, ServerMonster $monster) {
        $this->entrance->playerEntrance($character, $monster, $this->attackData);

        $this->mergeMessages($this->entrance->getMessages());

        $spellDamage = $this->attackData['spell_damage'];

        if ($this->entrance->isEnemyEntranced()) {
            $this->doSpellDamage($character, $monster, $spellDamage, true);

            return $this;
        }

        $this->mergeMessages($this->entrance->getMessages());

        if ($this->canHit->canPlayerAutoHit($character)) {
            $this->addMessage('You dance along in the shadows, the enemy doesn\'t see you. Strike now!', 'regular');

            $this->doSpellDamage($character, $monster, $spellDamage, true);

            return $this;
        }

        if ($this->canHit->canPlayerCastSpell($character, $monster, $this->isVoided)) {
            if ($monster->getMonsterStat('ac') > $spellDamage) {
                $this->addMessage('Your weapon was blocked!', 'enemy-action');
            } else {
                $this->doSpellDamage($character, $monster, $spellDamage);
            }
        } else {
            $this->addMessage('Your spell fizzled and failed!', 'enemy-action');

            $this->secondaryAttack($character, $monster);
        }

        return $this;
    }

    public function doSpellDamage(Character $character, ServerMonster $monster, int $spellDamage, bool $entranced = false) {
        if ($spellDamage > 0) {
            $this->spellDamage($character, $monster, $spellDamage, $entranced);
        }

        $this->heal($character);

        $this->secondaryAttack($character, $monster);
    }

    protected function secondaryAttack(Character $character, ServerMonster $monster) {
        if (!$this->isVoided) {
            $this->affixLifeStealingDamage($character, $monster);
            $this->affixDamage($character, $monster);
            $this->ringDamage();
        } else {
            $this->addMessage('You are voided, none of your rings or enchantments fire ...', 'enemy-action');
        }
    }


    protected function affixDamage(Character $character, ServerMonster $monster) {
        $damage = $this->affixes->getCharacterAffixDamage($character, $monster, $this->attackData);

        if ($damage > 0) {
            $this->monsterHealth -= $damage;
        }

        $this->mergeMessages($this->affixes->getMessages());

        $this->affixes->clearMessages();
    }

    protected function affixLifeStealingDamage(Character $character, ServerMonster $monster) {
        if ($this->monsterHealth <= 0) {
            return;
        }

        $lifeStealing = $this->affixes->getAffixLifeSteal($character, $monster, $this->attackData);

        $damage = $monster->getHealth() * $lifeStealing;

        if ($damage > 0) {
            $this->monsterHealth   -= $damage;
            $this->characterHealth += $damage;

            $maxCharacterHealth = $this->characterCacheData->getCachedCharacterData($character, 'health');

            if ($this->characterHealth >= $maxCharacterHealth) {
                $this->characterHealth = $maxCharacterHealth;
            }
        }

        $this->mergeMessages($this->affixes->getMessages());

        $this->affixes->clearMessages();
    }

    protected function ringDamage() {
        $ringDamage = $this->attackData['ring_damage'];

        if ($ringDamage > 0) {
            $this->monsterHealth -= ($ringDamage - $ringDamage * $this->attackData['damage_deduction']);

            $this->addMessage('Your rings hit for: ' . number_format($ringDamage), 'player-action');
        }
    }

    public function spellDamage(Character $character, ServerMonster $monster, int $spellDamage, bool $entranced = false) {

        $monsterSpellEvasion = $monster->getMonsterStat('spell_evasion');

        if (!$entranced) {
            if ($monsterSpellEvasion > 1) {
                $this->addMessage('The enemy evades your magic!', 'enemy-action');

                return;
            }

            $evasion = 100 - (100 - 100 * $monsterSpellEvasion);

            if (rand(1, 100) > $evasion) {
                $this->addMessage('The enemy evades your magic!', 'enemy-action');

                return;
            }
        }

        $criticality = $this->characterCacheData->getCachedCharacterData($character, 'skills')['criticality'];

        if (rand(1, 100) > (100 - 100 * $criticality)) {
            $this->addMessage('Your magic radiates across the plane. Even The Creator is terrified! (Critical strike!)', 'player-action');

            $spellDamage *= 2;
        }

        $totalDamage = $spellDamage - $spellDamage * $this->attackData['damage_deduction'];

        $this->monsterHealth -= $totalDamage;

        $this->addMessage('Your damage spell(s) hits ' . $monster->getName() . ' for: ' . number_format($totalDamage), 'player-action');

        $this->specialAttacks->setCharacterHealth($this->characterHealth)
                             ->setMonsterHealth($this->monsterHealth)
                             ->doCastDamageSpecials($character, $this->attackData);

        $this->characterHealth = $this->specialAttacks->getCharacterHealth();
        $this->monsterHealth   = $this->specialAttacks->getMonsterHealth();

        $this->mergeMessages($this->specialAttacks->getMessages());

        $this->specialAttacks->clearMessages();
    }

    public function heal(Character $character) {
        $healFor = $this->attackData['heal_for'];

        if ($healFor > 0) {
            $criticality = $this->characterCacheData->getCachedCharacterData($character, 'skills')['criticality'];

            if (rand(1, 100) > (100 - 100 * $criticality)) {
                $this->addMessage('The heavens open and your wounds start to heal over (Critical heal!)', 'player-action');

                $healFor *= 2;
            }

            $this->characterHealth += $healFor;

            if ($this->characterHealth > $this->characterCacheData->getCachedCharacterData($character, 'health')) {
                $this->characterHealth = $this->characterCacheData->getCachedCharacterData($character, 'health');
            }

            $this->addMessage('Your healing spell(s) heals you for: ' . number_format($healFor), 'player-action');

            $this->specialAttacks->doCastHealSpecials($character, $this->attackData);

            $this->characterHealth = $this->specialAttacks->getCharacterHealth();

            $this->mergeMessages($this->specialAttacks->getMessages());

            $this->specialAttacks->clearMessages();
        }
    }
}
