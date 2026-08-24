<?php

namespace App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Models\Character;
use App\Game\Battle\ServerFight\BattleBase;

class DoubleCast extends BattleBase
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

            $this->addMessage('Magic crackles through the air as you cast again!', 'regular');

            $damage = $attackData['spell_damage'];

            $damage = $damage + $damage * 0.15;

            if ($this->isRaidBoss && $damage > self::MAX_DAMAGE_FOR_RAID_BOSSES) {
                $damage = self::MAX_DAMAGE_FOR_RAID_BOSSES;
            }

            if ($attackData['damage_deduction'] > 0.0) {
                $this->addMessage('The Plane weakens your ability to do full damage!', 'enemy-action');

                $damage = $damage - $damage * $attackData['damage_deduction'];
            }

            $this->doBaseAttack($damage);
        }
    }

    private function doBaseAttack(int $damage)
    {
        $this->monsterHealth -= $damage;

        $this->addMessage('Your spell(s) hits for: '.number_format($damage), 'player-action');
    }
}
