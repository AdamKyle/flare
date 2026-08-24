<?php

namespace App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Models\Character;
use App\Game\Battle\ServerFight\BattleBase;

class TripleAttack extends BattleBase
{
    public function handleAttack(Character $character, array $attackData)
    {
        $extraActionData = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

        if ($extraActionData['has_item']) {

            if (! ($extraActionData['chance'] >= 1)) {
                if (! $this->chanceCalculator->passesPercentage($extraActionData['chance'] * 100)) {
                    return;
                }
            }

            $this->addMessage('A fury takes over you. You notch the arrows thrice at the enemy\'s direction', 'regular');

            $damage = $attackData['weapon_damage'];

            $damage = $damage + $damage * 0.15;

            if ($this->isRaidBoss && $damage > self::MAX_DAMAGE_FOR_RAID_BOSSES) {
                $damage = self::MAX_DAMAGE_FOR_RAID_BOSSES;
            }

            if ($attackData['damage_deduction'] > 0.0) {
                $this->addMessage('The Plane weakens your ability to do full damage!', 'enemy-action');

                $damage = $damage - $damage * $attackData['damage_deduction'];
            }

            for ($i = 3; $i > 0; $i--) {
                $this->doBaseAttack($damage);
            }
        }
    }

    private function doBaseAttack(int $damage)
    {
        $this->monsterHealth -= $damage;

        $this->addMessage('You hit for (weapon - triple attack) '.number_format($damage), 'player-action');
    }
}
