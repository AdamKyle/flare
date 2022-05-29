<?php


namespace App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;

class VampireThirst extends BattleBase {

    public function handleAttack(Character $character, array $attackData, bool $isPvp = false) {
        $extraActionData = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

        if (!($extraActionData['chance'] >= 1)) {
            if (!(rand(1, 100) > (100 - 100 * $extraActionData['chance']))) {
                return;
            }
        }

        $dur    = $this->characterCacheData->getCachedCharacterData($character, 'dur_modded');
        $damage = $dur + $dur * 0.15;

        $this->addMessage('There is a thirst, child, it\'s in your soul! Lash out and kill!', 'regular', $isPvp);

        if ($attackData['damage_deduction'] > 0.0) {
            $this->addMessage('The Plane weakens your ability to do full damage!', 'enemy-action', $isPvp);

            $damage = $damage - $damage * $attackData['damage_deduction'];
        }

        $this->doBaseAttack($character, $damage);
    }

    protected function doBaseAttack(Character $character, int $damage, bool $isPvp = false) {
        $this->monsterHealth   -= $damage;
        $this->characterHealth += $damage;

        $maxHealth = $this->characterCacheData->getCachedCharacterData($character, 'health');

        if ($this->characterHealth > $maxHealth) {
            $this->characterHealth = $maxHealth;
        }

        $this->addMessage('You hit for (thirst!) (and healed for) ' . number_format($damage), 'player-action', $isPvp);

        if ($isPvp) {
            $this->addDefenderMessage('You are besieged from the shadows by the enemy. The blood of your life is being drained away!' . number_format($damage), 'enemy-action');
        }
    }
}
