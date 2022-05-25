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

class Defend extends BattleBase {

    private int $monsterHealth;

    private int $characterHealth;

    private array $attackData;

    private bool $isVoided;

    private CharacterCacheData $characterCacheData;

    private Entrance $entrance;

    private CanHit $canHit;

    private Affixes $affixes;

    private SpecialAttacks $specialAttacks;

    public function __construct(CharacterCacheData $characterCacheData, Entrance $entrance, CanHit $canHit, Affixes $affixes, SpecialAttacks $specialAttacks) {
        parent::__construct();

        $this->characterCacheData = $characterCacheData;
        $this->entrance           = $entrance;
        $this->canHit             = $canHit;
        $this->affixes            = $affixes;
        $this->specialAttacks     = $specialAttacks;
    }

    public function setMonsterHealth(int $monsterHealth): Defend {
        $this->monsterHealth = $monsterHealth;

        return $this;
    }

    public function setCharacterHealth(int $characterHealth): Defend {
        $this->characterHealth = $characterHealth;

        return $this;
    }

    public function setCharacterAttackData(Character $character, bool $isVoided): Defend {

        $this->attackData = $this->characterCacheData->getDataFromAttackCache($character, $isVoided ? 'voided_defend' : 'defend');
        $this->isVoided   = $isVoided;

        return $this;
    }

    public function defend(Character $character, ServerMonster $serverMonster): Defend {

        $this->entrance->playerEntrance($character, $serverMonster, $this->attackData);

        $this->mergeMessages($this->entrance->getMessages());

        if ($this->entrance->isEnemyEntranced()) {
            $this->secondaryAttack($character, $serverMonster);

            return $this;
        }

        if ($this->canHit->canPlayerAutoHit($character)) {
            $this->addMessage('You dance along in the shadows, the enemy doesn\'t see you. Strike now!', 'regular');

            $this->secondaryAttack($character, $serverMonster);

            return $this;
        }

        $this->secondaryAttack($character, $serverMonster);

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

    protected function secondaryAttack(Character $character, ServerMonster $monster) {
        if (!$this->isVoided) {
            $this->affixLifeStealingDamage($character, $monster);
            $this->affixDamage($character, $monster);
            $this->ringDamage();
        } else {
            $this->addMessage('You are voided, none of your rings or enchantments fire ...', 'enemy-action');
        }

        $this->vampireSpecial($character, $this->attackData);
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

    protected function vampireSpecial(Character $character, array $attackData) {
        if ($character->classType()->isVampire()) {
            $this->specialAttacks
                 ->setCharacterHealth($this->characterHealth)
                 ->setMonsterHealth($this->monsterHealth)
                 ->vampireThirst($character, $attackData);

            $this->characterHealth = $this->specialAttacks->getCharacterHealth();
            $this->monsterHealth   = $this->specialAttacks->getMonsterHealth();

            $this->mergeMessages($this->specialAttacks->getMessages());

            $this->specialAttacks->clearMessages();
        }
    }
}
