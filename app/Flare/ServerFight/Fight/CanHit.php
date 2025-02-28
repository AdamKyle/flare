<?php

namespace App\Flare\ServerFight\Fight;

use App\Flare\Models\Character;
use App\Flare\ServerFight\Monster\ServerMonster;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;

class CanHit
{
    private CharacterCacheData $characterCacheData;

    const MINIMUM_DAMAGE_FOR_A_PLAYER = 500_000_000;

    public function __construct(CharacterCacheData $characterCacheData)
    {
        $this->characterCacheData = $characterCacheData;
    }

    public function canPlayerHitMonster(Character $character, ServerMonster $monster, bool $isPlayerVoided)
    {

        if ($monster->isRaidBossMonster() && $character->getInformation()->buildTotalAttack() < self::MINIMUM_DAMAGE_FOR_A_PLAYER) {
            return true;
        }

        $defenderAgi = $monster->getMonsterStat('agi');
        $characterToHit = $this->characterCacheData->getCachedCharacterData($character, 'to_hit_stat');
        $statValue = $this->characterCacheData->getCachedCharacterData($character, $isPlayerVoided ? $characterToHit : $characterToHit . '_modded');
        $characterAccuracy = $this->characterCacheData->getCachedCharacterData($character, 'skills')['accuracy'];
        $enemyDodge = $monster->getMonsterStat('dodge');

        if ($characterAccuracy >= 1) {
            return true;
        }

        if ($enemyDodge >= 1) {
            return false;
        }

        $playerToHit = $statValue * 0.20;
        $enemyAgi = $defenderAgi * 0.20;

        if ($playerToHit < 50) {
            $playerToHit = $statValue;
        }

        if ($enemyAgi < 50) {
            $enemyAgi = $defenderAgi;
        }

        return ($playerToHit + $playerToHit * $characterAccuracy) > ($enemyAgi + $enemyAgi * $enemyDodge);
    }

    public function canPlayerAutoHit(Character $character): bool
    {
        if (! $character->classType()->isThief()) {
            return false;
        }

        $extraActionInfo = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

        if ($extraActionInfo['has_item']) {

            if ($extraActionInfo['chance'] >= 1) {
                return true;
            }

            return rand(1, 100) > (100 - 100 * $extraActionInfo['chance']);
        }

        return false;
    }

    public function canMonsterHitPlayer(Character $character, ServerMonster $monster, bool $isPlayerVoided)
    {
        $monsterToHit = $monster->getMonsterStat('to_hit_base') * 0.20;
        $characterAgi = $this->characterCacheData->getCachedCharacterData($character, $isPlayerVoided ? 'agi' : 'agi_modded') * 0.20;
        $monsterAccuracy = $monster->getMonsterStat('accuracy');
        $characterDodge = $this->characterCacheData->getCachedCharacterData($character, 'skills')['dodge'];

        if ($characterDodge >= 1) {
            return false;
        }

        if ($monsterAccuracy >= 1) {
            return true;
        }

        if ($monsterToHit < 50) {
            $monsterToHit = $monster->getMonsterStat('to_hit_base');
        }

        if ($characterAgi < 50) {
            $characterAgi = $this->characterCacheData->getCachedCharacterData($character, $isPlayerVoided ? 'agi' : 'agi_modded');
        }

        return ($monsterToHit + $monsterToHit * $monsterAccuracy) > ($characterAgi + $characterAgi * $characterDodge);
    }

    public function canPlayerCastSpell(Character $character, ServerMonster $monster, bool $isPlayerVoided)
    {
        if ($monster->isRaidBossMonster() && $character->getInformation()->buildTotalAttack() < self::MINIMUM_DAMAGE_FOR_A_PLAYER) {
            return true;
        }

        $defenderAgi = $monster->getMonsterStat('agi');
        $characterToHit = $this->characterCacheData->getCachedCharacterData($character, 'to_hit_stat');
        $statValue = $this->characterCacheData->getCachedCharacterData($character, $isPlayerVoided ? $characterToHit : $characterToHit . '_modded');
        $characterAccuracy = $this->characterCacheData->getCachedCharacterData($character, 'skills')['casting_accuracy'];
        $enemyDodge = $monster->getMonsterStat('dodge');

        if ($characterAccuracy >= 1) {
            return true;
        }

        if ($enemyDodge >= 1) {
            return false;
        }

        $playerToHit = $statValue * 0.20;
        $enemyAgi = $defenderAgi * 0.20;

        if ($playerToHit < 50) {
            $playerToHit = $statValue;
        }

        if ($enemyAgi < 50) {
            $enemyAgi = $defenderAgi;
        }

        return ($playerToHit + $playerToHit * $characterAccuracy) > ($enemyAgi + $enemyAgi * $enemyDodge);
    }

    public function canMonsterCastSpell(Character $character, ServerMonster $monster, bool $isPlayerVoided)
    {
        $monsterToHit = $monster->getMonsterStat('to_hit_base') * 0.2;
        $characterAgi = $this->characterCacheData->getCachedCharacterData($character, $isPlayerVoided ? 'agi' : 'agi_modded') * 0.2;
        $monsterAccuracy = $monster->getMonsterStat('casting_accuracy');
        $characterDodge = $this->characterCacheData->getCachedCharacterData($character, 'skills')['dodge'];

        if ($characterDodge >= 1) {
            return false;
        }

        if ($monsterAccuracy >= 1) {
            return true;
        }

        if ($monsterToHit < 50) {
            $monsterToHit = $monster->getMonsterStat('to_hit_base');
        }

        if ($characterAgi < 50) {
            $characterAgi = $this->characterCacheData->getCachedCharacterData($character, $isPlayerVoided ? 'agi' : 'agi_modded');
        }

        return ($monsterToHit + $monsterToHit * $monsterAccuracy) > ($characterAgi + $characterAgi * $characterDodge);
    }
}
