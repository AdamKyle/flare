<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;

class BuccaneersBarrage extends BattleBase
{
    public function handleAttack(Character $character, array $attackData): void
    {
        $extraActionData = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

        if (! $extraActionData['has_item']) {
            return;
        }

        if (! ($extraActionData['chance'] >= 1)) {
            if (! (rand(1, 100) > (100 - 100 * $extraActionData['chance']))) {
                return;
            }
        }

        $weaponDamage = $attackData['weapon_damage'];

        $this->addMessage('You brace behind your shield and fire a brutal Buccaneer\'s Barrage!', 'regular');

        $this->fireShot($character, $attackData, $weaponDamage * 0.25, 'Shot 1');
        $this->fireShot($character, $attackData, $weaponDamage * 0.15, 'Shot 2');
        $this->fireShot($character, $attackData, $weaponDamage * 0.05, 'Shot 3');
    }

    private function fireShot(Character $character, array $attackData, float $damage, string $shotLabel): void
    {
        if ($attackData['damage_deduction'] > 0.0) {
            $this->addMessage('The Plane weakens your ability to do full damage!', 'enemy-action');

            $damage = $damage - $damage * $attackData['damage_deduction'];
        }

        if ($this->isRaidBoss && $damage > self::MAX_DAMAGE_FOR_RAID_BOSSES) {
            $damage = self::MAX_DAMAGE_FOR_RAID_BOSSES;
        }

        $this->monsterHealth -= (int) $damage;

        $this->addMessage('You hit for (Buccaneer\'s Barrage - '.$shotLabel.') '.number_format((int) $damage), 'player-action');
    }
}
