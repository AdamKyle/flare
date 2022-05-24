<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;

class HammerSmash extends BattleBase {

    private int $characterHealth;

    private int $monsterHealth;

    private CharacterCacheData $characterCacheData;

    public function __construct(CharacterCacheData $characterCacheData) {
        parent::__construct();

        $this->characterCacheData = $characterCacheData;
    }

    public function setCharacterHealth(int $characterHealth): HammerSmash {
        $this->characterHealth = $characterHealth;

        return $this;
    }

    public function setMonsterHealth(int $monsterHealth): HammerSmash {
        $this->monsterHealth = $monsterHealth;

        return $this;
    }

    public function getCharacterHealth(): int {
        return $this->characterHealth;
    }

    public function getMonsterHealth(): int {
        return $this->monsterHealth;
    }

    public function handleHammerSmash(Character $character, array $attackData) {
        $extraActionData = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

        if ($extraActionData['has_item']) {

            if (!(rand(1, 100) > (100 - 100 * $extraActionData['chance']))) {
                return;
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

    protected function doBaseAttack(int $damage) {
        $this->addMessage('You raise your mighty hammer high above your head and bring it crashing down!', 'regular');

        $this->monsterHealth -= $damage;

        $this->addMessage('You hit for (Hammer): ' . number_format($damage), 'player-action');
    }

    protected function doAfterShocks(int $damage) {
        $roll = rand (1, 100);
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
