<?php

namespace App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Models\Character;
use App\Game\Battle\ServerFight\BattleBase;
use App\Game\Character\CharacterAttack\Values\ClassSpecialAttackType;

class BeastStomp extends BattleBase
{
    public function handleAttack(Character $character, array $attackData): void
    {
        $extraActionData = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

        if (! $extraActionData['has_item']) {
            return;
        }

        if (! isset($extraActionData['type']) || $extraActionData['type'] !== ClassSpecialAttackType::BEAST_STOMP->value) {
            return;
        }

        if (! ($extraActionData['chance'] >= 1)) {
            if (! $this->chanceCalculator->passesPercentage($extraActionData['chance'] * 100)) {
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

        $truncatedStompDamage = $this->truncatedDamage($stompDamage);

        $this->monsterHealth -= $truncatedStompDamage;
        $this->addMessage('You hit for (Beast Stomp) '.number_format($truncatedStompDamage), 'player-action');

        $earthCrustDamage = $this->truncatedDamage($this->monsterHealth * 0.25);

        if ($this->isRaidBoss && $earthCrustDamage > self::MAX_DAMAGE_FOR_RAID_BOSSES) {
            $earthCrustDamage = self::MAX_DAMAGE_FOR_RAID_BOSSES;
        }

        $this->monsterHealth -= $earthCrustDamage;
        $this->addMessage('The earth crust shatters beneath the enemy (Earth Crust) '.number_format($earthCrustDamage), 'player-action');
    }
}
