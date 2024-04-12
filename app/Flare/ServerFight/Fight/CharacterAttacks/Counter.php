<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Monster\ServerMonster;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;

class Counter extends BattleBase {

    public function __construct(CharacterCacheData $characterCacheData) {
        parent::__construct($characterCacheData);
    }

    public function setIsAttackerVoided(bool $voided) {
        $this->isVoided = $voided;
    }

    public function pvpCounter(Character $attacker, Character $defender) {
        $defenderAttackData = $this->characterCacheData->getDataFromAttackCache($defender, $this->isEnemyVoided ? 'voided_attack' : 'attack');
        $weaponDamage       = $defenderAttackData['weapon_damage'];
        $attackerAc         = $this->characterCacheData->getCachedCharacterData($attacker, 'ac');

        $attackerCounterResistance = $this->characterCacheData->getCachedCharacterData($attacker, 'counter_resistance_chance');
        $defenderCounterChance     = $this->characterCacheData->getCachedCharacterData($defender, 'counter_chance');

        if ($defenderCounterChance <= 0.0) {
            return false;
        }

        $defenderCounterChance -= $attackerCounterResistance;

        if ($defenderCounterChance <= 0.0) {
            $defenderCounterChance = 0.05;
        }

        $canCounter = $this->canCounter($defenderCounterChance);

        if (!$canCounter) {
            return;
        }

        if ($weaponDamage > $attackerAc) {
            $this->characterHealth -= $weaponDamage;

            $this->addAttackerMessage('You counter the enemies attack for: ' . number_format($weaponDamage), 'player-action');
            $this->addDefenderMessage('You were countered in your attack for: '. number_format($weaponDamage), 'enemy-action');

        } else {
            $this->addDefenderMessage('Your counter as blocked!', 'player-action');
            $this->addAttackerMessage('You Blocked the enemies counter!', 'player-action');
        }
    }

    public function monsterCounter(Character $character, ServerMonster $monster) {
        $characterAc = $this->characterCacheData->getCachedCharacterData($character, 'ac');

        $characterCounterResistance = $this->characterCacheData->getCachedCharacterData($character, 'counter_resistance_chance');
        $monsterCounterChance       = $monster->getMonsterStat('counter_chance');

        $monsterCounterChance -= $characterCounterResistance;

        $canCounter = $this->canCounter($monsterCounterChance);

        if (!$canCounter) {
            return;
        }

        $monsterAttack = $monster->buildAttack();

        if ($monsterAttack > $characterAc) {
            $this->addMessage('The enemy counters your attack for: ' . number_format($monsterAttack), 'enemy-action');

            $this->characterHealth -= $monsterAttack;
        } else {
            $this->addMessage('You blocked the enemy counter attack!', 'player-action');
        }

    }

    public function playerCounter(Character $character, ServerMonster $monster) {
        $monsterAc                = $monster->getMonsterStat('ac');
        $monsterCounterResistance = $monster->getMonsterStat('counter_resistance_chance');
        $characterCounterChance   = $this->characterCacheData->getCachedCharacterData($character, 'counter_chance');

        $characterCounterChance -= $monsterCounterResistance;

        if (!$this->canCounter($characterCounterChance)) {
            return;
        }

        $weaponDamage = $this->characterCacheData->getDataFromAttackCache($character, $this->isVoided ? 'voided_attack' : 'attack')['weapon_damage'];

        if ($weaponDamage > $monsterAc) {
            $this->monsterHealth -= $weaponDamage;

            $this->addMessage('You counter the enemies attack for: ' . number_format($weaponDamage), 'player-action');
        } else {
            $this->addMessage('The enemy managed to block your counter!','enemy-action');
        }
    }

    protected function canCounter($chance) {

        if ($chance > 0.0) {
            $roll = rand(1, 100);
            $roll = $roll + $roll * $chance;

            if ($roll > 75) {
                return true;
            }
        }

        return false;
    }
}
