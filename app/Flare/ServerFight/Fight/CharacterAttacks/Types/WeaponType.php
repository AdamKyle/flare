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

class WeaponType extends BattleBase {

    private array $attackData;

    private bool $isVoided;


    private Entrance $entrance;

    private CanHit $canHit;

    private Affixes $affixes;

    private SpecialAttacks $specialAttacks;

    public function __construct(CharacterCacheData $characterCacheData, Entrance $entrance, CanHit $canHit, Affixes $affixes, SpecialAttacks $specialAttacks) {
        parent::__construct($characterCacheData);

        $this->entrance           = $entrance;
        $this->canHit             = $canHit;
        $this->affixes            = $affixes;
        $this->specialAttacks     = $specialAttacks;
    }

    public function setCharacterAttackData(Character $character, bool $isVoided): WeaponType {

        $this->attackData = $this->characterCacheData->getDataFromAttackCache($character, $isVoided ? 'voided_attack' : 'attack');
        $this->isVoided   = $isVoided;

        return $this;
    }

    public function doWeaponAttack(Character $character, ServerMonster $serverMonster): WeaponType {

        $this->entrance->playerEntrance($character, $serverMonster, $this->attackData);

        $this->mergeMessages($this->entrance->getMessages());

        $weaponDamage = $this->attackData['weapon_damage'];

        if ($this->entrance->isEnemyEntranced()) {
            $this->weaponAttack($character, $serverMonster->getName(), $weaponDamage);

            return $this;
        }

        if ($this->canHit->canPlayerAutoHit($character)) {
            $this->addMessage('You dance along in the shadows, the enemy doesn\'t see you. Strike now!', 'regular');

            $this->weaponAttack($character, $serverMonster->getName(), $weaponDamage);

            return $this;
        }

        if ($this->canHit->canPlayerHitMonster($character, $serverMonster, $this->isVoided)) {

            if ($serverMonster->getMonsterStat('ac') > $weaponDamage) {
                $this->addMessage('Your weapon was blocked!', 'enemy-action');
            } else {
                $this->weaponAttack($character, $serverMonster->getName(), $weaponDamage);
            }
        } else {
            $this->addMessage('Your attack missed!', 'enemy-action');

            $this->secondaryAttack($character, $serverMonster);
        }

        return $this;
    }

    public function resetMessages() {
        $this->clearMessages();
        $this->entrance->clearMessages();
    }

    public function weaponAttack(Character $character, ServerMonster $monster, int $weaponDamage) {
        $this->weaponDamage($character, $monster, $weaponDamage);
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

    public function weaponDamage(Character $character, string $monsterName, int $weaponDamage) {
        $criticality = $this->characterCacheData->getCachedCharacterData($character, 'skills')['criticality'];

        if (rand(1, 100) > (100 - 100 * $criticality)) {
            $this->addMessage('You become overpowered with rage! (Critical strike!)', 'player-action');

            $weaponDamage *= 2;
        }

        $totalDamage = $weaponDamage - $weaponDamage * $this->attackData['damage_deduction'];

        $this->monsterHealth -= $totalDamage;

        $this->addMessage('Your weapon hits ' . $monsterName . ' for: ' . number_format($totalDamage), 'player-action');

        $this->specialAttacks->setCharacterHealth($this->characterHealth)
                             ->setMonsterHealth($this->monsterHealth)
                             ->doWeaponSpecials($character, $this->attackData);

        $this->mergeMessages($this->specialAttacks->getMessages());

        $this->characterHealth = $this->specialAttacks->getCharacterHealth();
        $this->monsterHealth   = $this->specialAttacks->getMonsterHealth();

        $this->specialAttacks->clearMessages();
    }

    protected function ringDamage() {
        $ringDamage = $this->attackData['ring_damage'];

        if ($ringDamage > 0) {
            $this->monsterHealth -= ($ringDamage - $ringDamage * $this->attackData['damage_deduction']);

            $this->addMessage('Your rings hit for: ' . number_format($ringDamage), 'player-action');
        }
    }
}
