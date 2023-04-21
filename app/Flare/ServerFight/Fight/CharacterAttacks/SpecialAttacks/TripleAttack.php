<?php


namespace App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;

class TripleAttack extends BattleBase {

    public function handleAttack(Character $character, array $attackData, bool $isPvp = false) {
        $extraActionData = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

        if ($extraActionData['has_item']) {

            if (!($extraActionData['chance'] >= 1)) {
                if (!(rand(1, 100) > (100 - 100 * $extraActionData['chance']))) {
                    return;
                }
            }

            $this->addMessage('A fury takes over you. You notch the arrows thrice at the enemy\'s direction', 'regular', $isPvp);

            $damage = $attackData['weapon_damage'];

            $damage = $damage + $damage * 0.15;

            if ($attackData['damage_deduction'] > 0.0) {
                $this->addMessage('The Plane weakens your ability to do full damage!', 'enemy-action', $isPvp);

                $damage = $damage - $damage * $attackData['damage_deduction'];
            }

            for ($i = 3; $i > 0; $i--) {
                $this->doBaseAttack($damage, $isPvp);
            }
        }
    }

    protected function doBaseAttack(int $damage, bool $isPvp = false) {
        $this->monsterHealth -= $damage;

        $this->addMessage('You hit for (weapon - triple attack) ' . number_format($damage), 'player-action', $isPvp);

        if ($isPvp) {
            $this->addDefenderMessage('The enemies arrows fly at you in a fury, doing: ' . number_format($damage), 'enemy-action');
        }
    }
}
