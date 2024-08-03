<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;

class ThiefBackStab extends BattleBase
{
    public function backstab(Character $character, array $attackData)
    {
        $extraActionData = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

        if ($extraActionData['has_item']) {

            if (! ($extraActionData['chance'] >= 1)) {
                if (! (rand(1, 100) > (100 - 100 * $extraActionData['chance']))) {
                    return;
                }
            }

            $damage = $this->characterCacheData->getCachedCharacterData($character, 'dex_modded') * 0.30;

            if ($attackData['damage_deduction'] > 0.0) {
                $this->addMessage('The Plane weakens your ability to do full damage!', 'enemy-action', true);

                $damage = $damage - $damage * $attackData['damage_deduction'];
            }

            $this->doAttack($damage);
        }
    }

    protected function doAttack(int $damage)
    {
        $this->addMessage('Sneaking behind the enemy and moving through the shadows you prepare to strike!', 'regular', true);

        $this->monsterHealth -= $damage;

        $this->addMessage('You hit for (Back Stab): '.number_format($damage), 'player-action', true);

        $this->addDefenderMessage('The enemy stabs you in back doing: '.number_format($damage), 'enemy-action');
    }
}
