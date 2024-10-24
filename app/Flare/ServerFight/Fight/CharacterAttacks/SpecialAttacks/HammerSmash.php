<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;

class HammerSmash extends BattleBase
{
    /**
     * Handle the hammer smash attack.
     *
     * @return void
     */
    public function handleHammerSmash(Character $character, array $attackData)
    {
        $extraActionData = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

        if ($extraActionData['has_item']) {

            if (! ($extraActionData['chance'] >= 1)) {
                if (! (rand(1, 100) > (100 - 100 * $extraActionData['chance']))) {
                    return;
                }
            }

            $damage = $this->characterCacheData->getCachedCharacterData($character, 'str_modded') * 0.30;

            if ($attackData['damage_deduction'] > 0.0) {
                $this->addMessage('The Plane weakens your ability to do full damage!', 'enemy-action');

                $damage = $damage - $damage * $attackData['damage_deduction'];
            }

            $this->doBaseAttack($damage);
            $this->doAfterShocks($damage);
        }
    }

    /**
     * Do the base hammer smash attack.
     *
     * @return void
     */
    protected function doBaseAttack(int $damage)
    {
        $this->addMessage('You raise your mighty hammer high above your head and bring it crashing down!', 'regular');

        $this->monsterHealth -= $damage;

        $this->addMessage('You hit for (Hammer): ' . number_format($damage), 'player-action');
    }

    /**
     * Do after shocks.
     *
     * - Players have a 60% chance
     * - Damage looses 15% for each after shock.
     *
     * @return void
     */
    protected function doAfterShocks(int $damage)
    {
        $roll = rand(1, 100);
        $roll = $roll + $roll * .60;

        if ($roll > 99) {
            $this->addMessage('The enemy feels the aftershocks of the Hammer Smash!', 'regular');

            for ($i = 3; $i > 0; $i--) {
                $damage -= $damage * 0.15;

                if ($damage >= 1) {
                    $this->monsterHealth -= $damage;

                    $this->addMessage('Aftershock hits for: ' . number_format($damage), 'player-action');
                }
            }
        }
    }
}
