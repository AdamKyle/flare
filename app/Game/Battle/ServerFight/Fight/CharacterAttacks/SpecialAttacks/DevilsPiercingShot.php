<?php

namespace App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Models\Character;
use App\Game\Battle\ServerFight\BattleBase;
use App\Game\Character\CharacterAttack\Values\ClassSpecialAttackType;

class DevilsPiercingShot extends BattleBase
{
    public function handleAttack(Character $character, array $attackData): void
    {
        $extraActionData = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

        if (! $extraActionData['has_item']) {
            return;
        }

        if (! isset($extraActionData['type']) || $extraActionData['type'] !== ClassSpecialAttackType::DEVILS_PIERCING_SHOT->value) {
            return;
        }

        if (! ($extraActionData['chance'] >= 1)) {
            if (! $this->chanceCalculator->passesPercentage($extraActionData['chance'] * 100)) {
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

        $truncatedMainDamage = $this->truncatedDamage($mainDamage);

        $this->monsterHealth -= $truncatedMainDamage;
        $this->addMessage('You hit for (Devil\'s Piercing Shot) '.number_format($truncatedMainDamage), 'player-action');

        $bleedRates = [0.17, 0.14, 0.08, 0.04];

        foreach ($bleedRates as $index => $rate) {
            $bleedDamage = $this->truncatedDamage($this->monsterHealth * $rate);

            if ($this->isRaidBoss && $bleedDamage > self::MAX_DAMAGE_FOR_RAID_BOSSES) {
                $bleedDamage = self::MAX_DAMAGE_FOR_RAID_BOSSES;
            }

            $this->monsterHealth -= $bleedDamage;
            $this->addMessage('The wound bleeds (Bleed '.($index + 1).') '.number_format($bleedDamage), 'player-action');
        }
    }
}
