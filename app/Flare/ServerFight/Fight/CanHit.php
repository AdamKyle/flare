<?php

namespace App\Flare\ServerFight\Fight;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\Monster\ServerMonster;

class CanHit {

    private CharacterCacheData $characterCacheData;

    public function __construct(CharacterCacheData $characterCacheData) {
        $this->characterCacheData = $characterCacheData;
    }

    public function canPlayerHitMonster(Character $character, ServerMonster $monster, bool $isPlayerVoided) {
        $defenderAgi       = $monster->getMonsterStat('agi');
        $characterToHit    = $this->characterCacheData->getCachedCharacterData($character, 'to_hit_stat');
        $statValue         = $this->characterCacheData->getCachedCharacterData($character, $isPlayerVoided ? $characterToHit : $characterToHit . '_modded');
        $characterAccuracy = $this->characterCacheData->getCachedCharacterData($character, 'skills')['accuracy'];
        $enemyDodge        = $monster->getMonsterStat('dodge');

        if ($enemyDodge >= 1) {
            return false;
        }

        if ($characterAccuracy >= 1) {
            return false;
        }

        $playerToHit = $statValue * 0.02;
        $enemyAgi    = $defenderAgi * 0.02;

        if ($playerToHit < 10) {
            $playerToHit = $statValue;
        }

        if ($enemyAgi < 10) {
            $enemyAgi = $defenderAgi;
        }

        return ($playerToHit + $playerToHit * $characterAccuracy) > ($enemyAgi + $enemyAgi * $enemyDodge);
    }

    public function canPlayerAutoHit(Character $character): bool {
        $extraActionInfo = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

        if ($extraActionInfo['has_item']) {

            if ($extraActionInfo['chance'] >= 1) {
                return true;
            }

            return rand(1, 100) > (100 - 100 * $extraActionInfo['chance']);
        }

        return false;
    }

    public function canMonsterHitPlayer(Character $character, ServerMonster $monster, bool $isPlayerVoided) {
        $monsterToHit    = $monster->getMonsterStat('to_hit_base') * 0.02;
        $characterAgi    = $this->characterCacheData->getCachedCharacterData($character, $isPlayerVoided ? 'agi' : 'agi_modded') * 0.02;
        $monsterAccuracy = $monster->getMonsterStat('accuracy');
        $characterDodge  = $this->characterCacheData->getCachedCharacterData($character, 'skills')['dodge'];

        if ($characterDodge >= 1) {
            return false;
        }

        if ($monsterAccuracy >= 1) {
            return true;
        }

        if ($monsterToHit < 10) {
            $monsterToHit = $monster->getMonsterStat('to_hit_base');
        }

        if ($characterAgi < 10) {
            $characterAgi = $this->characterCacheData->getCachedCharacterData($character, $isPlayerVoided ? 'agi' : 'agi_modded');
        }

        return ($monsterToHit + $monsterToHit * $monsterAccuracy) > ($characterAgi + $characterAgi * $characterDodge);
    }

    public function canMonsterCastSpell(Character $character, ServerMonster $monster, bool $isPlayerVoided) {
        $monsterToHit    = $monster->getMonsterStat('to_hit_base') * 0.02;
        $characterAgi    = $this->characterCacheData->getCachedCharacterData($character, $isPlayerVoided ? 'agi' : 'agi_modded') * 0.02;
        $monsterAccuracy = $monster->getMonsterStat('casting_accuracy');
        $characterDodge  = $this->characterCacheData->getCachedCharacterData($character, 'skills')['dodge'];

        if ($characterDodge >= 1) {
            return false;
        }

        if ($monsterAccuracy >= 1) {
            return true;
        }

        if ($monsterToHit < 10) {
            $monsterToHit = $monster->getMonsterStat('to_hit_base');
        }

        if ($characterAgi < 10) {
            $characterAgi = $this->characterCacheData->getCachedCharacterData($character, $isPlayerVoided ? 'agi' : 'agi_modded');
        }

        return ($monsterToHit + $monsterToHit * $monsterAccuracy) > ($characterAgi + $characterAgi * $characterDodge);
    }
}
