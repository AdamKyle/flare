<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;

class BookBindersFear extends BattleBase
{
    public function handleAttack(Character $character, array $attackData, bool $isPvp = false)
    {
        $extraActionData = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

        if (! $extraActionData['has_item']) {
            return;
        }

        if (! ($extraActionData['chance'] >= 1)) {
            if (! (rand(1, 100) > (100 - 100 * $extraActionData['chance']))) {
                return;
            }
        }

        $weaponDamage = $attackData['weapon_damage'];

        if ($extraActionData['amount'] > 1) {
            $damage = $weaponDamage + $weaponDamage * 0.55;

            $this->addMessage('You lunge at the enemy with both scratch awls and aim for the eyes!', 'regular', $isPvp);

            if ($attackData['damage_deduction'] > 0.0) {
                $this->addMessage('The Plane weakens your ability to do full damage!', 'enemy-action', $isPvp);

                $damage = $damage - $damage * $attackData['damage_deduction'];
            }

            for ($i = 1; $i <= 2; $i++) {
                $this->doBaseAttack($character, $damage);
            }

            return;
        }

        $damage = $weaponDamage * 0.22;

        $this->addMessage('Your fear beings to mount and you start rapidly stabbing the enemy!', 'regular', $isPvp);

        if ($attackData['damage_deduction'] > 0.0) {
            $this->addMessage('The Plane weakens your ability to do full damage!', 'enemy-action', $isPvp);

            $damage = $damage - $damage * $attackData['damage_deduction'];
        }

        for ($i = 1; $i <= 22; $i++) {
            $this->doBaseAttack($character, $damage);
        }
    }

    protected function doBaseAttack(Character $character, int $damage, bool $isPvp = false)
    {
        $this->monsterHealth -= $damage;
        $this->characterHealth += $damage;

        $maxHealth = $this->characterCacheData->getCachedCharacterData($character, 'health');

        if ($this->characterHealth > $maxHealth) {
            $this->characterHealth = $maxHealth;
        }

        $this->addMessage('You hit for (Book Binders Fear) '.number_format($damage), 'player-action', $isPvp);

        if ($isPvp) {
            $this->addDefenderMessage('The enemy violently stabs you repeatedly hoping and screaming that you die!'.number_format($damage), 'enemy-action');
        }
    }
}
