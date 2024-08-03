<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;

class AlchemistsRavenousDream extends BattleBase
{
    public function handleAttack(Character $character, array $attackData, bool $isPvp = false)
    {
        $extraActionData = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

        if ($extraActionData['has_item']) {

            if (! ($extraActionData['chance'] >= 1)) {
                if (! (rand(1, 100) > (100 - 100 * $extraActionData['chance']))) {
                    return;
                }
            }

            $this->addMessage('The world around you fades to blackness, your eyes glow red with rage. The enemy trembles.', 'regular', $isPvp);

            $this->multiAttack($character, $attackData, $isPvp);
        }
    }

    protected function multiAttack(Character $character, array $attackData, bool $isPvp = false)
    {
        $times = rand(2, 6);
        $originalTimes = $times;
        $percent = 0.10;

        while ($times > 0) {
            if ($times === $originalTimes) {

                $damage = $this->characterCacheData->getCachedCharacterData($character, 'int_modded') * $percent;

                if ($attackData['damage_deduction'] > 0.0) {
                    $this->addMessage('The plane weakens your ability to do full damage!', 'enemy-action', $isPvp);

                    $damage = $damage - $damage * $attackData['damage_deduction'];
                }

                $this->monsterHealth -= $damage;

                $this->addMessage('You hit for (Arcane Alchemist Ravenous Dream): '.number_format($damage), 'player-action', $isPvp);

                if ($isPvp) {
                    $this->addDefenderMessage('A blast of Arcanic energy blasts you for: '.number_format($damage), 'enemy-action');
                }
            } else {
                $damage = $this->characterCacheData->getCachedCharacterData($character, 'int_modded') * $percent;

                if ($attackData['damage_deduction'] > 0.0) {
                    $this->addMessage('The Plane weakens your ability to do full damage!', 'enemy-action', $isPvp);

                    $damage = $damage - $damage * $attackData['damage_deduction'];
                }

                if ($damage >= 1) {
                    $this->addMessage('The earth shakes as you cause a multitude of explosions to engulf the enemy.', 'regular', $isPvp);

                    $this->monsterHealth -= $damage;

                    $this->addMessage('You hit for (Arcane Alchemist Ravenous Dream): '.number_format($damage), 'player-action', $isPvp);

                    if ($isPvp) {
                        $this->addDefenderMessage('Terrifying dreams of ravenous alchemical monsters lash at your flesh doing: '.number_format($damage), 'enemy-action');
                    }
                }
            }

            $times--;
            $percent += 0.03;
        }
    }
}
