<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\Types;

use App\Falre\ServerFight\Fight\CharacterAttacks\Counter;
use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Fight\CanHit;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;
use App\Flare\ServerFight\Fight\Entrance;
use App\Flare\ServerFight\Monster\ServerMonster;

class WeaponType extends BattleBase {

    private Entrance $entrance;

    private CanHit $canHit;

    private SpecialAttacks $specialAttacks;

    public function __construct(CharacterCacheData $characterCacheData, Entrance $entrance, CanHit $canHit, SpecialAttacks $specialAttacks) {
        parent::__construct($characterCacheData);

        $this->entrance           = $entrance;
        $this->canHit             = $canHit;
        $this->specialAttacks     = $specialAttacks;
    }

    public function setCharacterAttackData(Character $character, bool $isVoided): WeaponType {

        $this->attackData = $this->characterCacheData->getDataFromAttackCache($character, $isVoided ? 'voided_attack' : 'attack');
        $this->isVoided   = $isVoided;

        return $this;
    }

    public function doPvpWeaponAttack(Character $attacker, Character $defender): WeaponType {
        $weaponDamage = $this->attackData['weapon_damage'];

        if (!$this->isEnemyEntranced) {
            $this->doPvpEntrance($attacker, $this->entrance);

            if ($this->isEnemyEntranced) {
                $this->pvpWeaponAttack($attacker, $defender, $weaponDamage);

                return $this;
            }
        } else if ($this->isEnemyEntranced) {
            $this->pvpWeaponAttack($attacker, $defender, $weaponDamage);

            return $this;
        }

        if ($this->canHit->canPlayerHitPlayer($attacker, $defender, $this->isVoided)) {
            if ($this->canBlock($weaponDamage, $this->getPvpCharacterAc($defender))) {
                $this->addAttackerMessage('Your attack was blocked', 'enemy-action');
                $this->addDefenderMessage('You managed to block the enemies attack', 'player-action');

                if ($this->allowSecondaryAttacks) {
                    $this->secondaryAttack($attacker, null, $this->characterCacheData->getCachedCharacterData($attacker, 'affix_damage_reduction'));
                }

                return $this;
            }

            $this->pvpWeaponAttack($attacker, $defender, $weaponDamage);
        } else {
            $this->addAttackerMessage('Your attack missed!', 'enemy-action');

            if ($this->allowSecondaryAttacks) {
                $this->secondaryAttack($attacker, null, $this->characterCacheData->getCachedCharacterData($attacker, 'affix_damage_reduction'));
            }
        }

        return $this;
    }

    public function doWeaponAttack(Character $character, ServerMonster $serverMonster): WeaponType {

        $weaponDamage = $this->attackData['weapon_damage'];

        if (!$this->isEnemyEntranced) {

            $this->doEnemyEntrance($character, $serverMonster, $this->entrance);

            if ($this->isEnemyEntranced) {
                $this->weaponAttack($character, $serverMonster, $weaponDamage);
                return $this;
            }

        } else if ($this->isEnemyEntranced) {
            $this->weaponAttack($character, $serverMonster, $weaponDamage);
            return $this;
        }

        if ($this->canHit->canPlayerAutoHit($character)) {
            $this->addMessage('You dance along in the shadows, the enemy doesn\'t see you. Strike now!', 'regular');

            $this->weaponAttack($character, $serverMonster, $weaponDamage);

            return $this;
        }

        if ($this->canHit->canPlayerHitMonster($character, $serverMonster, $this->isVoided)) {

            if ($this->canBlock($weaponDamage, $serverMonster->getMonsterStat('ac'))) {
                $this->addMessage('Your weapon was blocked!', 'enemy-action');
            } else {
                $this->weaponAttack($character, $serverMonster, $weaponDamage);
            }
        } else {
            $this->addMessage('Your attack missed!', 'enemy-action');

            if ($this->allowSecondaryAttacks) {
                $this->secondaryAttack($character, $serverMonster);
            }
        }

        return $this;
    }

    public function resetMessages() {
        $this->clearMessages();
        $this->entrance->clearMessages();
    }

    public function pvpWeaponAttack(Character $attacker, Character $defender, int $weaponDamage) {
        $this->pvpWeaponDamage($attacker, $defender, $weaponDamage);

        if ($this->allowSecondaryAttacks && !$this->abortCharacterIsDead) {
            $this->secondaryAttack($attacker, null, $this->characterCacheData->getCachedCharacterData($defender, 'affix_damage_reduction'), true);
        }
    }

    public function weaponAttack(Character $character, ServerMonster $monster, int $weaponDamage) {
        $this->weaponDamage($character, $monster->getName(), $weaponDamage);

        $this->doMonsterCounter($character, $monster);

        if ($this->characterHealth <= 0) {
            $this->abortCharacterIsDead = true;

            return;
        }

        if ($this->allowSecondaryAttacks) {
            $this->secondaryAttack($character, $monster);
        }
    }

    public function pvpWeaponDamage(Character $attacker, Character $defender, int $weaponDamage) {
        $weaponDamage = $this->getCriticalityDamage($attacker, $weaponDamage);

        $totalDamage = $weaponDamage - $weaponDamage * $this->attackData['damage_deduction'];

        $this->monsterHealth -= $totalDamage;

        $this->addDefenderMessage('Your weapon slices at the enemies flesh for: ' . number_format($totalDamage), 'player-action');
        $this->addAttackerMessage($attacker->name . ' strikes you with their weapon for: ' . number_format($totalDamage), 'enemy-action');

        $this->pvpCounter($attacker, $defender);

        if ($this->characterHealth <= 0) {
            $this->addAttackerMessage('You manage to kill the enemy in your counter Attack!', 'player-action');
            $this->addDefenderMessage('You were slaughtered by the counter attack!', 'enemy-action');

            $this->abortCharacterIsDead = true;

            return;
        }

        $this->specialAttacks->setCharacterHealth($this->characterHealth)
                             ->setMonsterHealth($this->monsterHealth)
                             ->doWeaponSpecials($attacker, $this->attackData, true);

        $this->mergeAttackerMessages($this->specialAttacks->getDefenderMessages());
        $this->mergeDefenderMessages($this->specialAttacks->getAttackerMessages());

        $this->characterHealth = $this->specialAttacks->getCharacterHealth();
        $this->monsterHealth   = $this->specialAttacks->getMonsterHealth();

        $this->specialAttacks->clearMessages();
    }

    public function weaponDamage(Character $character, string $monsterName, int $weaponDamage) {

        $weaponDamage = $this->getCriticalityDamage($character, $weaponDamage);

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

    protected function getCriticalityDamage(Character $character, int $weaponDamage) {
        $criticality = $this->characterCacheData->getCachedCharacterData($character, 'skills')['criticality'];

        if (rand(1, 100) > (100 - 100 * $criticality)) {
            $this->addMessage('You become overpowered with rage! (Critical strike!)', 'player-action');

            $weaponDamage *= 2;
        }

        return $weaponDamage;
    }
}
