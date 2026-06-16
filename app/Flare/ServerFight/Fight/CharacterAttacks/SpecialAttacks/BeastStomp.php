<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\Values\ClassAttackValue;

class BeastStomp extends BattleBase
{
    public function handleAttack(Character $character, array $attackData): void
    {
        $extraActionData = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

        if (! $extraActionData['has_item']) {
            return;
        }

        if (! isset($extraActionData['type']) || $extraActionData['type'] !== ClassAttackValue::BEAST_STOMP) {
            return;
        }

        if (! ($extraActionData['chance'] >= 1)) {
            if (! (rand(1, 100) > (100 - 100 * $extraActionData['chance']))) {
                return;
            }
        }

        $this->addMessage('You raise your hammer and bring down a Beast Stomp!', 'regular');

        $stompDamage = $attackData['weapon_damage'] * 2;

        if ($attackData['damage_deduction'] > 0.0) {
            $this->addMessage('The Plane weakens your ability to do full damage!', 'enemy-action');
            $stompDamage = $stompDamage - $stompDamage * $attackData['damage_deduction'];
        }

        if ($this->isRaidBoss && $stompDamage > self::MAX_DAMAGE_FOR_RAID_BOSSES) {
            $stompDamage = self::MAX_DAMAGE_FOR_RAID_BOSSES;
        }

        $this->monsterHealth -= (int) $stompDamage;
        $this->addMessage('You hit for (Beast Stomp) ' . number_format((int) $stompDamage), 'player-action');

        $earthCrustDamage = (int) ($this->monsterHealth * 0.25);

        if ($this->isRaidBoss && $earthCrustDamage > self::MAX_DAMAGE_FOR_RAID_BOSSES) {
            $earthCrustDamage = self::MAX_DAMAGE_FOR_RAID_BOSSES;
        }

        $this->monsterHealth -= $earthCrustDamage;
        $this->addMessage('The earth crust shatters beneath the enemy (Earth Crust) ' . number_format($earthCrustDamage), 'player-action');
    }
}
