<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;

class BloodyPuke extends BattleBase
{
    public function handleAttack(Character $character, array $attackData)
    {
        $extraActionData = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

        if ($extraActionData['has_item']) {

            if (! ($extraActionData['chance'] >= 1)) {
                if (! (rand(1, 100) > (100 - 100 * $extraActionData['chance']))) {
                    return;
                }
            }

            $this->addMessage('You drink and you drink and you drink ...', 'regular');

            $durModded = $character->getInformation()->statMod('dur');

            $damage = $durModded * 0.30;
            $damageToSuffer = $durModded * 0.15;

            if ($attackData['damage_deduction'] > 0.0) {
                $this->addMessage('The Plane weakens your ability to do full damage! You will still suffer the 15% damage for vomiting blood.', 'enemy-action');

                $damage = $damage - $damage * $attackData['damage_deduction'];
            }

            if ($this->isRaidBoss && $damage > self::MAX_DAMAGE_FOR_RAID_BOSSES) {
                $damage = self::MAX_DAMAGE_FOR_RAID_BOSSES;
            }

            $this->doBaseAttack($damage, $damageToSuffer);

            $this->characterHealth -= $damageToSuffer;
        }
    }

    protected function doBaseAttack(int $damage, int $damageToSuffer)
    {
        $this->monsterHealth -= $damage;

        $this->addMessage('You cannot hold it in, you vomit blood and bile so acidic your enemy cannot handle it! (You dealt: '.number_format($damage).')', 'player-action');
        $this->addMessage('You lost a lot of blood in your attack. (You took: '.number_format($damageToSuffer).')', 'enemy-action');
    }
}
