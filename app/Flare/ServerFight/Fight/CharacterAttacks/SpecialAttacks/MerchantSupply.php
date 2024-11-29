<?php

namespace App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Flare\Models\Character;
use App\Flare\ServerFight\BattleBase;

class MerchantSupply extends BattleBase
{
    /**
     * Handle the merchant supply attack
     */
    public function handleAttack(Character $character, array $attackData): void
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

            $this->addMessage('You stare the enemy down as pull a coin out of your pocket to flip ...', 'regular');

            if ($chance > 50) {
                $damage = $damage * 4;

                $damage = $this->damageDeduction($damage, $attackData['damage_deduction']);

                if ($this->isRaidBoss && $damage > self::MAX_DAMAGE_FOR_RAID_BOSSES) {
                    $damage = self::MAX_DAMAGE_FOR_RAID_BOSSES;
                }

                $this->addMessage('You flip the coin: Heads! You do 4x the damage for a total of: ' . number_format($damage), 'player-action');
            } else {
                $damage = $damage * 2;

                $damage = $this->damageDeduction($damage, $attackData['damage_deduction']);

                if ($this->isRaidBoss && $damage > self::MAX_DAMAGE_FOR_RAID_BOSSES) {
                    $damage = self::MAX_DAMAGE_FOR_RAID_BOSSES;
                }

                $this->addMessage('You flip the coin: Tails! You do 2x the damage for a total of: ' . number_format($damage), 'player-action');
            }


            $this->monsterHealth -= $damage;
        }
    }

    /**
     * Apply damage deduction.
     */
    protected function damageDeduction(int $damage, float $deduction): int
    {

        if ($deduction > 0.0) {
            $this->addMessage('The Plane weakens your ability to do full damage!', 'enemy-action');

            return intval(floor($damage - $damage * $deduction));
        }

        return $damage;
    }
}
