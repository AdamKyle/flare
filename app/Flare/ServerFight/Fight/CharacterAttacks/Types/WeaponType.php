<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\Types;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Fight\Affixes;
use App\Flare\ServerFight\Fight\CanHit;
use App\Flare\ServerFight\Fight\Entrance;
use App\Flare\ServerFight\Monster\ServerMonster;

class WeaponType extends BattleBase {

    private int $monsterHealth;

    private int $characterHealth;

    private array $attackData;

    private bool $isVoided;

    private CharacterCacheData $characterCacheData;

    private Entrance $entrance;

    private CanHit $canHit;

    private Affixes $affixes;

    public function __construct(CharacterCacheData $characterCacheData, Entrance $entrance, CanHit $canHit, Affixes $affixes) {
        parent::__construct();

        $this->characterCacheData = $characterCacheData;
        $this->entrance           = $entrance;
        $this->canHit             = $canHit;
        $this->affixes            = $affixes;
    }

    public function setMonsterHealth(int $monsterHealth): WeaponType {
        $this->monsterHealth = $monsterHealth;

        return $this;
    }

    public function setCharacterHealth(int $characterHealth): WeaponType {
        $this->characterHealth = $characterHealth;

        return $this;
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
            $this->weaponAttack($character, $serverMonster, $weaponDamage);

            return $this;
        }

        if ($this->canHit->canPlayerAutoHit($character)) {
            $this->addMessage('You dance along in the shadows, the enemy doesn\'t see you. Strike now!', 'regular');

            $this->weaponAttack($character, $serverMonster, $weaponDamage);

            return $this;
        }

        if ($this->canHit->canPlayerHitMonster($character, $serverMonster, $this->isVoided)) {

            if ($serverMonster->getMonsterStat('ac') > $weaponDamage) {
                $this->addMessage('Your weapon was blocked!', 'enemy-action');
            } else {
                $this->weaponAttack($character, $serverMonster, $weaponDamage);
            }
        } else {
            $this->addMessage('Your attack missed!', 'enemy-action');
        }

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

    public function weaponAttack(Character $character, ServerMonster $monster, int $weaponDamage) {
        $this->weaponDamage($character, $monster, $weaponDamage);
        $this->affixLifeStealingDamage($character, $monster);
        $this->affixDamage($character, $monster);
        $this->ringDamage();
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
        $damage = $this->affixes->getAffixLifeSteal($character, $monster, $this->attackData);

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

    protected function weaponDamage(Character $character, ServerMonster $monster, int $weaponDamage) {
        $criticality = $this->characterCacheData->getCachedCharacterData($character, 'skills')['criticality'];

        if (rand(1, 100) > (100 - 100 * $criticality)) {
            $this->addMessage('You become overpowered with rage! (Critical strike!)', 'player-action');

            $weaponDamage *= 2;
        }

        $totalDamage = $weaponDamage - $weaponDamage * $this->attackData['damage_deduction'];

        $this->monsterHealth -= $totalDamage;

        $this->addMessage('Your weapon hits ' . $monster->getName() . ' for: ' . number_format($totalDamage), 'player-action');
    }

    protected function ringDamage() {
        $ringDamage = $this->attackData['ring_damage'];

        if ($ringDamage > 0) {
            $this->monsterHealth -= ($ringDamage - $ringDamage * $this->attackData['damage_deduction']);

            $this->addMessage('Your rings hit for: ' . number_format($ringDamage), 'player-action');
        }
    }
}
