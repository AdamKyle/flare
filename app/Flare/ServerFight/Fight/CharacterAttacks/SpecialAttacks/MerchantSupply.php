<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;

class MerchantSupply extends BattleBase
{
    /**
     * Handle the merchant supply attack
     */
    public function handleAttack(Character $character, array $attackData, bool $isPvp = false): void
    {
        $extraActionData = $this->characterCacheData->getCachedCharacterData($character, 'extra_action_chance');

        if ($extraActionData['has_item']) {

            if (! ($extraActionData['chance'] >= 1)) {
                if (! (rand(1, 100) > (100 - 100 * $extraActionData['chance']))) {
                    return;
                }
            }

            $damage = $attackData['weapon_damage'];
            $chance = rand(1, 100);

            $this->addMessage('You stare the enemy down as pull a coin out of your pocket to flip ...', 'regular', $isPvp);

            if ($isPvp) {
                $this->addDefenderMessage('You watch the enemy produce a small coin that they flip in the air!', 'enemy-action');
            }

            if ($chance > 50) {
                $damage = $damage * 4;

                $damage = $this->damageDeduction($damage, $attackData['damage_deduction'], $isPvp);

                $this->addMessage('You flip the coin: Heads! You do 4x the damage for a total of: '.number_format($damage), 'player-action', $isPvp);

            } else {
                $damage = $damage * 2;

                $damage = $this->damageDeduction($damage, $attackData['damage_deduction'], $isPvp);

                $this->addMessage('You flip the coin: Tails! You do 2x the damage for a total of: '.number_format($damage), 'player-action', $isPvp);
            }

            if ($isPvp) {
                $this->addDefenderMessage('The coin lands, they smile. You take: '.number_format($damage).' damage.', 'enemy-action');
            }

            $this->monsterHealth -= $damage;
        }
    }

    /**
     * Apply damage deduction.
     */
    protected function damageDeduction(int $damage, float $deduction, bool $isPvp = false): int
    {

        if ($deduction > 0.0) {
            $this->addMessage('The Plane weakens your ability to do full damage!', 'enemy-action', $isPvp);

            return intval(floor($damage - $damage * $deduction));
        }

        return $damage;
    }
}
