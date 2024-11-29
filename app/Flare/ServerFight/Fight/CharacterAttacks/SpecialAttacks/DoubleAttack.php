<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;

class DoubleAttack extends BattleBase
{
    public function handleAttack(Character $character, array $attackData)
    {
        $extraActionData = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

        if ($extraActionData['has_item']) {

            if (! ($extraActionData['chance'] >= 1)) {
                if (! (rand(1, 100) > (100 - 100 * $extraActionData['chance']))) {
                    return;
                }
            }

            $this->addMessage('The strength of your rage courses through your veins!', 'regular');

            $damage = $attackData['weapon_damage'];

            $damage = $damage + $damage * 0.15;

            if ($this->isRaidBoss && $damage > self::MAX_DAMAGE_FOR_RAID_BOSSES) {
                $damage = self::MAX_DAMAGE_FOR_RAID_BOSSES;
            }

            if ($attackData['damage_deduction'] > 0.0) {
                $this->addMessage('The Plane weakens your ability to do full damage!', 'enemy-action');

                $damage = $damage - $damage * $attackData['damage_deduction'];
            }

            for ($i = 2; $i > 0; $i--) {
                $this->doBaseAttack($damage);
            }
        }
    }

    protected function doBaseAttack(int $damage)
    {
        $this->monsterHealth -= $damage;

        $this->addMessage('You hit for (weapon - double attack) ' . number_format($damage), 'player-action');
    }
}
