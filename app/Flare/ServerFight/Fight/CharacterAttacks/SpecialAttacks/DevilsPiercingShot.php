<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\Values\ClassAttackValue;

class DevilsPiercingShot extends BattleBase
{
    public function handleAttack(Character $character, array $attackData): void
    {
        $extraActionData = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

        if (! $extraActionData['has_item']) {
            return;
        }

        if (! isset($extraActionData['type']) || $extraActionData['type'] !== ClassAttackValue::DEVILS_PIERCING_SHOT) {
            return;
        }

        if (! ($extraActionData['chance'] >= 1)) {
            if (! (rand(1, 100) > (100 - 100 * $extraActionData['chance']))) {
                return;
            }
        }

        $this->addMessage('You draw your bow and loose a Devil\'s Piercing Shot!', 'regular');

        $mainDamage = $attackData['weapon_damage'] * 2;

        if ($attackData['damage_deduction'] > 0.0) {
            $this->addMessage('The Plane weakens your ability to do full damage!', 'enemy-action');
            $mainDamage = $mainDamage - $mainDamage * $attackData['damage_deduction'];
        }

        if ($this->isRaidBoss && $mainDamage > self::MAX_DAMAGE_FOR_RAID_BOSSES) {
            $mainDamage = self::MAX_DAMAGE_FOR_RAID_BOSSES;
        }

        $this->monsterHealth -= (int) $mainDamage;
        $this->addMessage('You hit for (Devil\'s Piercing Shot) '.number_format((int) $mainDamage), 'player-action');

        $bleedRates = [0.17, 0.14, 0.08, 0.04];

        foreach ($bleedRates as $index => $rate) {
            $bleedDamage = (int) ($this->monsterHealth * $rate);

            if ($this->isRaidBoss && $bleedDamage > self::MAX_DAMAGE_FOR_RAID_BOSSES) {
                $bleedDamage = self::MAX_DAMAGE_FOR_RAID_BOSSES;
            }

            $this->monsterHealth -= $bleedDamage;
            $this->addMessage('The wound bleeds (Bleed '.($index + 1).') '.number_format($bleedDamage), 'player-action');
        }
    }
}
