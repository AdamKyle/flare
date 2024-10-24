<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;

class DoubleHeal extends BattleBase
{
    public function handleHeal(Character $character, array $attackData): int
    {
        $extraActionData = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

        if ($extraActionData['has_item']) {

            if (! ($extraActionData['chance'] >= 1)) {
                if (! (rand(1, 100) > (100 - 100 * $extraActionData['chance']))) {
                    return 0;
                }
            }

            $criticality = $this->characterCacheData->getCachedCharacterData($character, 'skills')['criticality'];
            $healFor = $attackData['heal_for'];

            $this->addMessage('Your prayers were heard by The Creator and he grants you extra life!', 'regular');

            if (rand(1, 100) > (100 - 100 * $criticality)) {
                $this->addMessage('The heavens open and your wounds start to heal over (Critical heal!)', 'regular');

                return $healFor *= 2;
            }

            $healFor = $healFor + $healFor * 0.15;

            $this->addMessage('Your healing spell(s) heals for an additional: ' . number_format($healFor), 'player-action');

            return $healFor;
        }
    }
}
