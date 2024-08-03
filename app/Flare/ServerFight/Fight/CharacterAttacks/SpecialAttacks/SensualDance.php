<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;

class SensualDance extends BattleBase
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
            $damage = $weaponDamage * 0.30;

            $this->addMessage('You dance around the enemy, enticing it with your body. The dance of love. The dance of death!', 'regular', $isPvp);

            if ($attackData['damage_deduction'] > 0.0) {
                $this->addMessage('The Plane weakens your ability to do full damage!', 'enemy-action', $isPvp);

                $damage = $damage - $damage * $attackData['damage_deduction'];
            }

            for ($i = 1; $i <= 3; $i++) {
                $this->doBaseAttack($character, $damage);
            }

            return;
        }

        $damage = $weaponDamage * 0.15;

        $this->addMessage('Your dance becomes more aggressive, the cuts on the enemy are small nicks of devastating wounds!', 'regular', $isPvp);

        if ($attackData['damage_deduction'] > 0.0) {
            $this->addMessage('The Plane weakens your ability to do full damage!', 'enemy-action', $isPvp);

            $damage = $damage - $damage * $attackData['damage_deduction'];
        }

        for ($i = 1; $i <= 9; $i++) {
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

        $this->addMessage('You hit for (Sensual Dance) '.number_format($damage), 'player-action', $isPvp);

        if ($isPvp) {
            $this->addDefenderMessage('The enemy entices you. Causes you to become weak at the knees from their dancing skills!'.number_format($damage), 'enemy-action');
        }
    }
}
