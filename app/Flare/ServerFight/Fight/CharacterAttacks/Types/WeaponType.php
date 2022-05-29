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

    public function doPvpWeaponAttack(Character $attacker, Character $defender, bool $isAttackerVoided): WeaponType {
        $this->entrance->attackerEntrancesDefender($attacker, $this->attackData, $isAttackerVoided);

        $this->mergeAttackerMessages($this->entrance->getAttackerMessages());
        $this->mergeDefenderMessages($this->entrance->getDefenderMessages());

        $weaponDamage = $this->attackData['weapon_damage'];

        if ($this->entrance->isEnemyEntranced()) {
            $this->pvpWeaponAttack($attacker, $defender, $weaponDamage);

            return $this;
        }

        if ($this->canHit->canPlayerHitPlayer($attacker, $defender, $isAttackerVoided)) {
            if ($this->characterCacheData->getCachedCharacterData($defender, 'ac') > $weaponDamage) {
                $this->addAttackerMessage('Your attack was blocked', 'enemy-action');
                $this->addDefenderMessage('You managed to block the enemies attack', 'player-action');

                $this->secondaryAttack($attacker, null, $this->characterCacheData->getCachedCharacterData($attacker, 'affix_damage_reduction'));

                return $this;
            }

            $this->pvpWeaponAttack($attacker, $defender, $weaponDamage);
        } else {
            $this->addAttackerMessage('Your attack missed!', 'enemy-action');
        }

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

    public function pvpWeaponAttack(Character $attacker, Character $defender, int $weaponDamage) {
        $this->pvpWeaponDamage($attacker, $weaponDamage);
        $this->secondaryAttack($attacker, null, $this->characterCacheData->getCachedCharacterData($defender, 'affix_damage_reduction'), true);
    }

    public function weaponAttack(Character $character, ServerMonster $monster, int $weaponDamage) {
        $this->weaponDamage($character, $monster, $weaponDamage);
        $this->secondaryAttack($character, $monster);
    }

    protected function secondaryAttack(Character $character, ServerMonster $monster = null, float $affixReduction = 0.0, bool $isPvp = false) {
        if (!$this->isVoided) {
            $this->affixLifeStealingDamage($character, $monster, $affixReduction, $isPvp);
            $this->affixDamage($character, $monster, $affixReduction, $isPvp);
            $this->ringDamage($isPvp);
        } else {
            $this->addMessage('You are voided, none of your rings or enchantments fire ...', 'enemy-action');
        }
    }

    protected function affixDamage(Character $character, ServerMonster $monster = null, float $defenderDamageReduction = 0.0, bool $isPvp = false) {

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

            $this->addAttackerMessage('The enemy is able to reduce the damage of your affixes to: ' . number_format($damage), 'enemy-action');
            $this->addDefenderMessage('You manage to scour up some strength and resist the damage coming in to: ' . number_format($damage), 'regular');
        }

        if ($damage > 0) {
            $this->monsterHealth -= $damage;
        }

        $this->affixes->clearMessages();
    }

    protected function affixLifeStealingDamage(Character $character, ServerMonster $monster = null, float $affixDamageReduction = 0.0, bool $isPvp = false) {
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

            $this->addAttackerMessage('The defender reduced your enchantments damage to: ' . number_format($damage), 'enemy-action');
            $this->addDefenderMessage('You manage, by the skin of your teeth, to use the last of your magics to reduce their enchantment damage to: ' . number_format($damage), 'regular');
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

    public function pvpWeaponDamage(Character $attacker, int $weaponDamage) {
        $weaponDamage = $this->getCriticalityDamage($attacker, $weaponDamage);

        $totalDamage = $weaponDamage - $weaponDamage * $this->attackData['damage_deduction'];

        $this->monsterHealth -= $totalDamage;

        $this->addAttackerMessage('Your weapon slices at the enemies flesh for: ' . number_format($totalDamage), 'player_action');
        $this->addDefenderMessage($attacker->name . ' strikes you with their weapon for: ' . number_format($totalDamage), 'player_action');

        $this->specialAttacks->setCharacterHealth($this->characterHealth)
                             ->setMonsterHealth($this->monsterHealth)
                             ->doWeaponSpecials($attacker, $this->attackData, true);

        $this->mergeAttackerMessages($this->specialAttacks->getAttackerMessages());
        $this->mergeDefenderMessages($this->specialAttacks->getDefenderMessages());

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

    protected function ringDamage(bool $ispvp = false) {
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
