<?php


namespace App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;

class PrisonerRage extends BattleBase {

    public function handleAttack(Character $character, array $attackData, bool $isPvp = false) {
        $extraActionData = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

        if ($extraActionData['has_item']) {

            if (!($extraActionData['chance'] >= 1)) {
                if (!(rand(1, 100) > (100 - 100 * $extraActionData['chance']))) {
                    return;
                }
            }

            $this->addMessage('You cannot let them keep you prisoner! Lash out and kill!', 'regular', $isPvp);

            $damage = $attackData['weapon_damage'];

            $strToAdd = $character->getInformation()->statMod('str') * 0.15;

            $damage = $damage + $strToAdd;

            if ($attackData['damage_deduction'] > 0.0) {
                $this->addMessage('The Plane weakens your ability to do full damage!', 'enemy-action', $isPvp);

                $damage = $damage - $damage * $attackData['damage_deduction'];
            }

            $times = rand(1, 4);

            for ($i = 0; $i <= $times; $i++) {
                $this->doBaseAttack($damage, $isPvp);
            }
        }
    }

    protected function doBaseAttack(int $damage, bool $isPvp = false) {
        $this->monsterHealth -= $damage;

        $this->addMessage('You slash, you thrash, you bash and you crash your way through! (You dealt: '.number_format($damage).')', 'player-action', $isPvp);

        if ($isPvp) {
            $this->addDefenderMessage('The enemy has refused to allow you to make them your prisoner. Death is coming! Damage dealt: ' . number_formart($damage), 'enemey-action');
        }
    }
}
