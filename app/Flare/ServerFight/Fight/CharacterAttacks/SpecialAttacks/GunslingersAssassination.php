<?php


namespace App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;

class GunslingersAssassination extends BattleBase {

    public function handleAttack(Character $character, array $attackData, bool $isPvp = false) {
        $extraActionData = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

        if (!$extraActionData['has_item']) {
            return;
        }

        if (!($extraActionData['chance'] >= 1)) {
            if (!(rand(1, 100) > (100 - 100 * $extraActionData['chance']))) {
                return;
            }
        }

        $weaponDamage = $attackData['weapon_damage'];

        if ($extraActionData['amount'] > 1) {
            $damage = $weaponDamage * 0.4;

            $this->addMessage('you fire off your guns in rapid succession praying you kill the enemy or at least hit it!', 'regular', $isPvp);

            if ($attackData['damage_deduction'] > 0.0) {
                $this->addMessage('The Plane weakens your ability to do full damage!', 'enemy-action', $isPvp);

                $damage = $damage - $damage * $attackData['damage_deduction'];
            }

            $this->doBaseAttack($character, $damage);

            return;
        }

        $damage = $weaponDamage * 0.6;


        $this->addMessage('You take careful aim at the enemy and fire a single shot!', 'regular', $isPvp);

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

        $this->addMessage('You hit for (Gunslingers Assassination!) ' . number_format($damage), 'player-action', $isPvp);

        if ($isPvp) {
            $this->addDefenderMessage('Bullets come flying your way. They peirce the skin and cause you to bleed out!' . number_format($damage), 'enemy-action');
        }
    }
}
