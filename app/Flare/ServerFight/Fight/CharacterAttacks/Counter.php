<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Monster\ServerMonster;

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

        $attackerCounterResistance = $this->characterCacheData->getCachedCharacterData($attacker, 'counter_resistance');
        $defenderCounterChance     = $this->characterCacheData->getCachedCharacterData($defender, 'counter_chance');

        $defenderCounterChance -= $attackerCounterResistance;
        $canCounter             = $this->canCounter($defenderCounterChance);

        if (!$canCounter) {
            return;
        }

        if ($weaponDamage > $attackerAc) {
            $this->characterHealth -= $weaponDamage;

            $this->addDefenderMessage('You counter the enemies attack for: ' . number_format($weaponDamage), 'enemy-action');
            $this->addAttackerMessage('You were countered in your attack for: '. number_format($weaponDamage), 'enemy-action');
        } else {
            $this->addDefenderMessage('Your counter as blocked!', 'enemy-action');
        }
    }

    public function monsterCounter(Character $character, ServerMonster $monster) {
        $characterAc = $this->characterCacheData->getCachedCharacterData($character, 'ac');

        $characterCounterResistance = $this->characterCacheData->getCachedCharacterData($character, 'counter_resistance');
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
