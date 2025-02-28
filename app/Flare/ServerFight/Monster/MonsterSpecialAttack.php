<?php

namespace App\Flare\ServerFight\Monster;

use App\Flare\ServerFight\BattleBase;
use App\Flare\Values\RaidAttackTypesValue;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;

class MonsterSpecialAttack extends BattleBase
{
    const PHYSICAL_DAMAGE_AMOUNT = 0.15;

    const MAGICAL_ICE_ATTACK_DAMAGE_AMOUNT = 0.20;

    const BANSHEE_SCREAM_ATTACK_DAMAGE_AMOUNT = 0.22;

    const DELUSIONAL_MEMORIES_ATTACK_DAMAGE_AMOUNT = 0.25;

    public function __construct(CharacterCacheData $characterCacheData)
    {
        parent::__construct($characterCacheData);
    }

    /**
     * Do the special attack for monsters.
     *
     * @throws \Exception
     */
    public function doSpecialAttack(int $specialAttackType, int $damageStat, int $ac): void
    {

        $specialAttackType = new RaidAttackTypesValue($specialAttackType);

        $this->addMessage('The enemy charges at you with their special attack...', 'enemy-action');

        if ($specialAttackType->isPhysicalAttack()) {
            $this->doPhysicalDamage($damageStat, $ac);
        }

        if ($specialAttackType->isMagicalIceAttack()) {
            $this->doMagicalIceDamage($damageStat, $ac);
        }

        if ($specialAttackType->isDelusionalMemoriesAttack()) {
            $this->doDelusionalMemoriesAttack($damageStat, $ac);
        }

        if ($specialAttackType->isBansheeScreamAttack()) {
            $this->doBansheeScreamAttack($damageStat, $ac);
        }
    }

    private function doPhysicalDamage(int $damageStat, int $ac): void
    {
        $newDamage = $damageStat * self::PHYSICAL_DAMAGE_AMOUNT;

        if ($ac > $newDamage) {
            $this->addMessage('You manage to block the enemies special attack!', 'player-action');

            return;
        }

        $newDamage = $newDamage - $ac;

        $this->characterHealth -= $newDamage;

        $this->addMessage('The enemy lashes out in a physical rage. Their muscles bulging, their eyes blood shot! Death has come today child!', 'enemy-action');
        $this->addMessage('You block: ' . number_format($ac) . ' of the enemies special attack damage!', 'player-action');
        $this->addMessage('You take: ' . number_format($newDamage) . ' damage from the enemies special attack (Physical)!', 'enemy-action');
    }

    /**
     * Do Magical Ice Damage
     */
    private function doMagicalIceDamage(int $damageStat, int $ac): void
    {
        $newDamage = $damageStat * self::MAGICAL_ICE_ATTACK_DAMAGE_AMOUNT;

        if ($ac > $newDamage) {
            $this->addMessage('You manage to block the enemies special attack!', 'player-action');

            return;
        }

        $newDamage = $newDamage - $ac;

        $this->characterHealth -= $newDamage;

        $this->addMessage('The enemy begins their chant, the air gets colder - chilly. Your breath is seen on the air, your flesh begins to freeze!', 'enemy-action');
        $this->addMessage('You block: ' . number_format($ac) . ' of the enemies special attack damage!', 'player-action');
        $this->addMessage('You take: ' . number_format($newDamage) . ' damage from the enemies special attack (Magical)!', 'enemy-action');
    }

    private function doBansheeScreamAttack(int $damageStat, int $ac): void
    {
        $newDamage = $damageStat * self::BANSHEE_SCREAM_ATTACK_DAMAGE_AMOUNT;

        if ($ac > $newDamage) {
            $this->addMessage('You manage to block the enemies special attack!', 'player-action');

            return;
        }

        $newDamage = $newDamage - $ac;

        $this->characterHealth -= $newDamage;

        $this->addMessage('The howl of the wind picks up and your ears begin to bleed as the enemy starts to scream, louder and louder!', 'enemy-action');
        $this->addMessage('You block: ' . number_format($ac) . ' of the enemies special attack damage!', 'player-action');
        $this->addMessage('You take: ' . number_format($newDamage) . ' damage from the enemies special attack (Banshee scream)!', 'enemy-action');
    }

    private function doDelusionalMemoriesAttack(int $damageStat, int $ac): void
    {
        $newDamage = $damageStat * self::DELUSIONAL_MEMORIES_ATTACK_DAMAGE_AMOUNT;

        if ($ac > $newDamage) {
            $this->addMessage('You manage to block the enemies special attack!', 'player-action');

            return;
        }

        $newDamage = $newDamage - $ac;

        $this->characterHealth -= $newDamage;

        $this->addMessage('The enemy begins screaming frantically about things that make no sense. Their own delusional memories are thrashing at you!', 'enemy-action');
        $this->addMessage('You block: ' . number_format($ac) . ' of the enemies special attack damage!', 'player-action');
        $this->addMessage('You take: ' . $newDamage . ' damage from the enemies special attack (Delusional)!', 'enemy-action');
    }
}
