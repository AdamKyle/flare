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

    public function handleAmbush(Character $character, ServerMonster $monster, bool $isCharacterVoided = false): Ambush {

        $this->healthObject = [
            'character_health' => $this->characterCacheData->getCachedCharacterData($character, 'health'),
            'monster_health'   => $monster->getHealth(),
        ];

        if ($character->map->gameMap->mapType()->isPurgatory()) {
            $this->monsterAmbushesPlayer($character, $monster, $isCharacterVoided);
        } else {
            $this->playerAmbushesMonster($character, $monster, $isCharacterVoided);
        }

        return $this;
    }

    public function getHealthObject(): array {
        return $this->healthObject;
    }

    public function playerAmbushesMonster(Character $character, ServerMonster $serverMonster, bool $isPlayerVoided) {
        $characterAmbushResistance = $this->characterCacheData->getCachedCharacterData($character, 'ambush_resistance_chance');
        $characterAmbushChance     = $this->characterCacheData->getCachedCharacterData($character, 'ambush_chance');

        if ($this->canPlayerAmbushMonster($characterAmbushChance, $serverMonster->getMonsterStat('ambush_resistance_chance'))) {
            $this->addMessage('You spot the enemy! Now is the time to ambush!', 'player-action');

            $baseStat = $this->characterCacheData->getCachedCharacterData($character, $isPlayerVoided ? 'voided_base_stat' : 'base_stat');
            $damage   = $baseStat * 2;

            $this->healthObject['monster_health'] -= $damage;

            $this->addMessage('You strike the enemy in an ambush doing: ' . number_format($damage) . ' damage!', 'enemy-action');
        } else if ($this->canMonsterAmbushPlayer($serverMonster->getMonsterStat('ambush_chance'), $characterAmbushResistance)) {
            $this->addMessage('The enemies plotting and scheming comes to fruition!', 'enemy-action');

            $damageStat = $serverMonster->getMonsterStat('damage_stat');
            $damage     = $serverMonster->getMonsterStat($damageStat) * 2;

            $this->healthObject['character_health'] -= $damage;

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

            $this->healthObject['character_health'] -= $damage;

            $this->addMessage($serverMonster->getName() . ' strikes you in an ambush doing: ' . number_format($damage) . ' damage!', 'enemy-action');
        } else if ($this->canPlayerAmbushMonster($characterAmbushChance, $serverMonster->getMonsterStat('ambush_resistance_chance'))) {
            $this->addMessage('You spot the enemy! Now is the time to ambush!', 'player-action');

            $baseStat = $this->characterCacheData->getCachedCharacterData($character, $isPlayerVoided ? 'voided_base_stat' : 'base_stat');
            $damage   = $baseStat * 2;

            $this->healthObject['monster_health'] -= $damage;

            $this->addMessage('You strike the enemy in an ambush doing: ' . number_format($damage) . ' damage!', 'enemy-action');
        }
    }

    public function canPlayerAmbushMonster(float $ambushChance, float $monsterAmbushResistance): bool {
        if ($monsterAmbushResistance >= 1) {
            return false;
        }

        if ($ambushChance >= 1) {
            return true;
        }

        $chance = $ambushChance - $monsterAmbushResistance;

        return rand (1, 100) > (100 - 100 * $chance);
    }

    public function canMonsterAmbushPlayer(float $ambushChance, float $playerAmbushResistance): bool {
        if ($playerAmbushResistance >= 1) {
            return false;
        }

        if ($ambushChance >= 1) {
            return true;
        }

        $chance = $ambushChance - $playerAmbushResistance;

        return rand (1, 100) > (100 - 100 * $chance);
    }
}
