<?php

namespace App\Flare\ServerFight\Fight;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Monster\ServerMonster;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;

class Ambush extends BattleBase
{
    private array $healthObject;

    public function __construct(CharacterCacheData $characterCacheData)
    {
        parent::__construct($characterCacheData);
    }

    public function handleAmbush(Character $character, ServerMonster $monster, bool $isCharacterVoided = false): Ambush
    {

        $this->healthObject = [
            'current_character_health' => $this->characterCacheData->getCachedCharacterData($character, 'health'),
            'current_monster_health' => $monster->getHealth(),
        ];

        if ($character->map->gameMap->mapType()->isPurgatory()) {
            $this->monsterAmbushesPlayer($character, $monster, $isCharacterVoided);
        } else {
            $this->playerAmbushesMonster($character, $monster, $isCharacterVoided);
        }

        return $this;
    }

    public function getHealthObject(): array
    {
        return $this->healthObject;
    }

    public function playerAmbushesMonster(Character $character, ServerMonster $serverMonster, bool $isPlayerVoided)
    {
        $characterAmbushResistance = $this->characterCacheData->getCachedCharacterData($character, 'ambush_resistance_chance');
        $characterAmbushChance = $this->characterCacheData->getCachedCharacterData($character, 'ambush_chance');

        if ($this->canPlayerAmbushMonster($characterAmbushChance, $serverMonster->getMonsterStat('ambush_resistance_chance'))) {

            $this->addMessage('You spot the enemy! Now is the time to ambush!', 'player-action');

            $baseStat = $this->characterCacheData->getCachedCharacterData($character, $isPlayerVoided ? 'voided_base_stat' : 'base_stat');
            $damage = $baseStat * 2;

            if ($serverMonster->isRaidBossMonster() && $damage > self::MAX_DAMAGE_FOR_RAID_BOSSES) {
                $damage = self::MAX_DAMAGE_FOR_RAID_BOSSES;
            }

            $this->healthObject['current_monster_health'] -= $damage;

            $this->addMessage('You strike the enemy in an ambush doing: ' . number_format($damage) . ' damage!', 'player-action');
        } elseif ($this->canMonsterAmbushPlayer($serverMonster->getMonsterStat('ambush_chance'), $characterAmbushResistance)) {


            if ($serverMonster->isRaidBossMonster() && $characterAmbushChance <= 0 && $character->getInformation()->buildTotalAttack() < self::MINIMUM_DAMAGE_FOR_A_PLAYER) {
                $this->addMessage('The enemy spots you from the shadows. They contemplate their ambush and then laugh to them selves as they walk from the shadows. "Child, I could have ambushed you, alas lets see what you have to offer!"', 'enemy-action');

                return;
            }

            $this->addMessage('The enemies plotting and scheming comes to fruition!', 'enemy-action');

            $damage = $serverMonster->buildAttack() * 2;

            $this->healthObject['current_character_health'] -= $damage;

            $this->addMessage($serverMonster->getName() . ' strikes you in an ambush doing: ' . number_format($damage) . ' damage!', 'enemy-action');
        }
    }

    public function monsterAmbushesPlayer(Character $character, ServerMonster $serverMonster, bool $isPlayerVoided)
    {
        $characterAmbushResistance = $this->characterCacheData->getCachedCharacterData($character, 'ambush_resistance_chance');
        $characterAmbushChance = $this->characterCacheData->getCachedCharacterData($character, 'ambush_chance');

        if ($this->canMonsterAmbushPlayer($serverMonster->getMonsterStat('ambush_chance'), $characterAmbushResistance)) {


            if ($serverMonster->isRaidBossMonster() && $characterAmbushChance <= 0) {
                $this->addMessage('The enemy spots you from the shadows. They contemplate their ambush and then laugh to them selves as they walk from the shadows. "Child, I could have ambushed you, alas lets see what you have to offer!"', 'enemy-action');

                return;
            }

            $this->addMessage('The enemies plotting and scheming comes to fruition!', 'enemy-action');

            $damageStat = $serverMonster->getMonsterStat('damage_stat');
            $damage = $serverMonster->getMonsterStat($damageStat) * 2;

            $this->healthObject['current_character_health'] -= $damage;

            $this->addMessage($serverMonster->getName() . ' strikes you in an ambush doing: ' . number_format($damage) . ' damage!', 'enemy-action');
        } elseif ($this->canPlayerAmbushMonster($characterAmbushChance, $serverMonster->getMonsterStat('ambush_resistance_chance'))) {

            $this->addMessage('You spot the enemy! Now is the time to ambush!', 'player-action');

            $baseStat = $this->characterCacheData->getCachedCharacterData($character, $isPlayerVoided ? 'voided_base_stat' : 'base_stat');
            $damage = $baseStat * 2;

            $this->healthObject['current_monster_health'] -= $damage;

            $this->addMessage('You strike the enemy in an ambush doing: ' . number_format($damage) . ' damage!', 'player-action');
        }
    }

    public function canPlayerAmbushMonster(float $ambushChance, float $monsterAmbushResistance): bool
    {

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

        $roll = rand(1, 100);
        $dc = 100 - (100 * $chance);

        return $roll > $dc;
    }

    public function canMonsterAmbushPlayer(float $ambushChance, float $playerAmbushResistance): bool
    {

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
