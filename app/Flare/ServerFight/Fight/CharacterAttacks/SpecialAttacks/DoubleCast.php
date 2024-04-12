<?php


namespace App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;

class DoubleCast extends BattleBase {

    public function handleAttack(Character $character, array $attackData, bool $isPvp = false) {
        $extraActionData = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

        if ($extraActionData['has_item']) {

            if (!($extraActionData['chance'] >= 1)) {
                if (!(rand(1, 100) > (100 - 100 * $extraActionData['chance']))) {
                    return;
                }
            }

            $this->addMessage('Magic crackles through the air as you cast again!', 'regular', $isPvp);

            $damage = $attackData['spell_damage'];

            $damage = $damage + $damage * 0.15;

            if ($attackData['damage_deduction'] > 0.0) {
                $this->addMessage('The Plane weakens your ability to do full damage!', 'enemy-action', $isPvp);

                $damage = $damage - $damage * $attackData['damage_deduction'];
            }

            $this->doBaseAttack($damage, $isPvp);
        }
    }

    protected function doBaseAttack(int $damage, bool $isPvp = false) {
        $this->monsterHealth -= $damage;

        $this->addMessage('Your spell(s) hits for: ' . number_format($damage), 'player-action', $isPvp);

        if ($isPvp) {
            $this->addDefenderMessage('The enemies spells fly at you, the energy in the air is electrifyingly terrifying: ' . number_format($damage), 'enemy-action');
        }
    }
}
