<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;

class HolySmite extends BattleBase
{
    public function handleAttack(Character $character, array $attackData)
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

        $damage = $this->characterCacheData->getCachedCharacterData($character, 'chr_modded');

        $damage = $damage + $damage * .60;

        $this->addMessage('You pray, you prepare - you smite your enemy!', 'regular');

        if ($attackData['damage_deduction'] > 0.0) {
            $this->addMessage('The Plane weakens your ability to do full damage!', 'enemy-action');

            $damage = $damage - $damage * $attackData['damage_deduction'];
        }

        if ($this->isRaidBoss && $damage > self::MAX_DAMAGE_FOR_RAID_BOSSES) {
            $damage = self::MAX_DAMAGE_FOR_RAID_BOSSES;
        }

        $this->doBaseAttack($character, $damage);
    }

    protected function doBaseAttack(Character $character, int $damage)
    {
        $this->monsterHealth -= $damage;
        $this->characterHealth += $damage;

        $maxHealth = $this->characterCacheData->getCachedCharacterData($character, 'health');

        if ($this->characterHealth > $maxHealth) {
            $this->characterHealth = $maxHealth;
        }

        $this->addMessage('You hit for (Holy Smite) ' . number_format($damage), 'player-action');
    }
}
