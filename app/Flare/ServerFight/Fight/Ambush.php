<?php

namespace App\Flare\ServerFight\Fight;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Monster\ServerMonster;

class Ambush extends BattleBase {

    private array $healthObject;

    public function __construct(CharacterCacheData $characterCacheData) {
        parent::__construct($characterCacheData);
    }

    public function handleAmbush(Character $character, ServerMonster $monster, bool $isCharacterVoided = false, bool $isRankFight = false): Ambush {

        $this->healthObject = [
            'current_character_health' => $this->characterCacheData->getCachedCharacterData($character, 'health'),
            'current_monster_health'   => $monster->getHealth(),
        ];

        if ($character->map->gameMap->mapType()->isPurgatory() && !$isRankFight) {
            $this->monsterAmbushesPlayer($character, $monster, $isCharacterVoided);
        } else {
            $this->playerAmbushesMonster($character, $monster, $isCharacterVoided);
        }

        return $this;
    }

    public function getHealthObject(): array {
        return $this->healthObject;
    }

    public function attackerAmbushesDefender(Character $attacker, Character $defender, bool $isAttackerVoided, array $healthObject, bool $isPvp = false): array {

        $attackerAmbushChance     = $this->characterCacheData->getCachedCharacterData($attacker, 'ambush_chance');
        $defenderAmbushResistance = $this->characterCacheData->getCachedCharacterData($defender, 'ambush_resistance_chance');

        if ($this->canPlayerAmbushMonster($attackerAmbushChance, $defenderAmbushResistance, $isPvp)) {
            $this->addAttackerMessage('You get the jump on the enemy!', 'player-action');

            $baseStat = $this->characterCacheData->getCachedCharacterData($attacker, $isAttackerVoided ? 'voided_base_stat' : 'base_stat');
            $damage   = $baseStat * 2;

            $healthObject['defender_health'] -= $damage;

            $damage = number_format($damage);

            if ($healthObject['defender_health'] <= 0) {
                $healthObject['defender_health'] = 0;

                $this->addAttackerMessage('Through plotting and planning, you slit the your enemies throat from behind. (Ambush!): ' . $damage, 'player-action');
                $this->addDefenderMessage($attacker->name . ' Got the jump on you! now your throats slit and your (possibly) dead ... You took: ' . $damage . ' damage.', 'enemy-action');
            } else {
                $this->addAttackerMessage('Through plotting and planning, you get the jump on the enemy! (Ambush!): ' . $damage, 'player-action');
                $this->addDefenderMessage($attacker->name . ' Got the jump on you! You took: ' . $damage . ' damage.', 'enemy-action');
            }
        }

        return $healthObject;
    }

    public function playerAmbushesMonster(Character $character, ServerMonster $serverMonster, bool $isPlayerVoided) {
        $characterAmbushResistance = $this->characterCacheData->getCachedCharacterData($character, 'ambush_resistance_chance');
        $characterAmbushChance     = $this->characterCacheData->getCachedCharacterData($character, 'ambush_chance');


        if ($this->canPlayerAmbushMonster($characterAmbushChance, $serverMonster->getMonsterStat('ambush_resistance_chance'))) {
            $this->addMessage('You spot the enemy! Now is the time to ambush!', 'player-action');

            $baseStat = $this->characterCacheData->getCachedCharacterData($character, $isPlayerVoided ? 'voided_base_stat' : 'base_stat');
            $damage   = $baseStat * 2;

            $this->healthObject['current_monster_health'] -= $damage;

            $this->addMessage('You strike the enemy in an ambush doing: ' . number_format($damage) . ' damage!', 'player-action');
        } else if ($this->canMonsterAmbushPlayer($serverMonster->getMonsterStat('ambush_chance'), $characterAmbushResistance)) {
            $this->addMessage('The enemies plotting and scheming comes to fruition!', 'enemy-action');

            $damage = $serverMonster->buildAttack() * 2;

            $this->healthObject['current_character_health'] -= $damage;

            $this->addMessage($serverMonster->getName() . ' strikes you in an ambush doing: ' . number_format($damage) . ' damage!', 'enemy-action');
        }
    }

    public function monsterAmbushesPlayer(Character $character, ServerMonster $serverMonster, bool $isPlayerVoided) {
        $characterAmbushResistance = $this->characterCacheData->getCachedCharacterData($character, 'ambush_resistance_chance');
        $characterAmbushChance     = $this->characterCacheData->getCachedCharacterData($character, 'ambush_chance');


        if ($this->canMonsterAmbushPlayer($serverMonster->getMonsterStat('ambush_chance'), $characterAmbushResistance)) {
            $this->addMessage('The enemies plotting and scheming comes to fruition!', 'enemy-action');

            $damageStat = $serverMonster->getMonsterStat('damage_stat');
            $damage     = $serverMonster->getMonsterStat($damageStat) * 2;

            $this->healthObject['current_character_health'] -= $damage;

            $this->addMessage($serverMonster->getName() . ' strikes you in an ambush doing: ' . number_format($damage) . ' damage!', 'enemy-action');
        } else if ($this->canPlayerAmbushMonster($characterAmbushChance, $serverMonster->getMonsterStat('ambush_resistance_chance'))) {
            $this->addMessage('You spot the enemy! Now is the time to ambush!', 'player-action');

            $baseStat = $this->characterCacheData->getCachedCharacterData($character, $isPlayerVoided ? 'voided_base_stat' : 'base_stat');
            $damage   = $baseStat * 2;

            $this->healthObject['current_monster_health'] -= $damage;

            $this->addMessage('You strike the enemy in an ambush doing: ' . number_format($damage) . ' damage!', 'player-action');
        }
    }

    public function canPlayerAmbushMonster(float $ambushChance, float $monsterAmbushResistance, bool $isPvp = false): bool {
        if ($monsterAmbushResistance >= 1) {
            return false;
        }

        if ($ambushChance >= 1) {
            return true;
        }

        if ($ambushChance <= 0.0) {
            return false;
        }

        $chance = $ambushChance - $monsterAmbushResistance;

        if ($chance <= 0.0 && $isPvp) {
            $chance = 0.05;
        }

        $roll = rand(1, 100);
        $dc   = 100 - (100 * $chance);

        return $roll > $dc;
    }

    public function canMonsterAmbushPlayer(float $ambushChance, float $playerAmbushResistance): bool {
        if ($playerAmbushResistance >= 1) {
            return false;
        }

        if ($ambushChance >= 1) {
            return true;
        }

        $chance = $ambushChance - $playerAmbushResistance;

        return rand(1, 100) > (100 - 100 * $chance);
    }
}
