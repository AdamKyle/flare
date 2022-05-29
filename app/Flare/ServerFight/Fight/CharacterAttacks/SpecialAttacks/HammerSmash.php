<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;

class HammerSmash extends BattleBase {

    public function handleHammerSmash(Character $character, array $attackData, bool $isPvp = false) {
        $extraActionData = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

        if ($extraActionData['has_item']) {

            if (!($extraActionData['chance'] >= 1)) {
                if (!(rand(1, 100) > (100 - 100 * $extraActionData['chance']))) {
                    return;
                }
            }

            $damage = $this->characterCacheData->getCachedCharacterData($character, 'str_modded') * 0.30;

            if ($attackData['damage_deduction'] > 0.0) {
                $this->addMessage('The Plane weakens your ability to do full damage!', 'enemy-action', $isPvp);

                $damage = $damage - $damage * $attackData['damage_deduction'];
            }

            $this->doBaseAttack($damage, $isPvp);
            $this->doAfterShocks($damage, $isPvp);
        }
    }

    protected function doBaseAttack(int $damage, bool $isPvp = false) {
        $this->addMessage('You raise your mighty hammer high above your head and bring it crashing down!', 'regular', $isPvp);

        $this->monsterHealth -= $damage;

        $this->addMessage('You hit for (Hammer): ' . number_format($damage), 'player-action', $isPvp);

        if ($isPvp) {
            $this->addDefenderMessage('The enemies hammer comes down with such a fury doing: ' . number_format($damage), 'enemy-action');
        }
    }

    protected function doAfterShocks(int $damage, bool $isPvp = false) {
        $roll = rand (1, 100);
        $roll = $roll + $roll * .60;

        if ($roll > 99) {
            $this->addMessage('The enemy feels the aftershocks of the Hammer Smash!', 'regular', $isPvp);


            for ($i = 3; $i > 0; $i--) {
                $damage -= $damage * 0.15;

                if ($damage >= 1) {
                    $this->monsterHealth -= $damage;

                    $this->addMessage('Aftershock hits for: ' . number_format($damage), 'player-action', $isPvp);

                    if ($isPvp) {
                        $this->addDefenderMessage('And after shock comes rumbling towards you, the earth is so violent: ' . number_format($damage), 'enemy-action');
                    }
                }
            }
        }
    }
}
