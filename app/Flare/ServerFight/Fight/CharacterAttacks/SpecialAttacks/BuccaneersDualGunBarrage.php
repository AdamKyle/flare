<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;
use App\Flare\Values\ClassAttackValue;

class BuccaneersDualGunBarrage extends BattleBase
{
    public function handleAttack(Character $character, array $attackData): void
    {
        $extraActionData = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

        if (! $extraActionData['has_item']) {
            return;
        }

        if (! isset($extraActionData['type']) || $extraActionData['type'] !== ClassAttackValue::BUCCANEERS_DUAL_GUN_BARRAGE) {
            return;
        }

        if (! ($extraActionData['chance'] >= 1)) {
            if (! (rand(1, 100) > (100 - 100 * $extraActionData['chance']))) {
                return;
            }
        }

        $weaponDamage = $attackData['weapon_damage'];

        $this->addMessage('You draw both guns and unleash a Buccaneer\'s Dual Gun Barrage!', 'regular');

        $this->fireShot($attackData, $weaponDamage * 0.75, 'Shot 1');
        $this->fireShot($attackData, $weaponDamage * 0.55, 'Shot 2');
        $this->fireShot($attackData, $weaponDamage * 0.35, 'Shot 3');
    }

    private function fireShot(array $attackData, float $damage, string $shotLabel): void
    {
        if ($attackData['damage_deduction'] > 0.0) {
            $this->addMessage('The Plane weakens your ability to do full damage!', 'enemy-action');

            $damage = $damage - $damage * $attackData['damage_deduction'];
        }

        if ($this->isRaidBoss && $damage > self::MAX_DAMAGE_FOR_RAID_BOSSES) {
            $damage = self::MAX_DAMAGE_FOR_RAID_BOSSES;
        }

        $this->monsterHealth -= (int) $damage;

        $this->addMessage('You hit for (Buccaneer\'s Dual Gun Barrage - ' . $shotLabel . ') ' . number_format((int) $damage), 'player-action');
    }
}
