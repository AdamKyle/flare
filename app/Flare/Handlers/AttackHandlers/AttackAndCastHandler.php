<?php

namespace App\Flare\Handlers\AttackHandlers;

use App\Flare\Handlers\WeaponAndMagicAttackBase;
use App\Flare\Builders\Character\AttackDetails\CharacterAttackBuilder;
use App\Flare\Handlers\AttackExtraActionHandler;

class AttackAndCastHandler extends WeaponAndMagicAttackBase {

    public function __construct(
        CharacterAttackBuilder $characterAttackBuilder,
        EntrancingChanceHandler $entrancingChanceHandler,
        AttackExtraActionHandler $attackExtraActionHandler,
        CastHandler $castHandler,
        ItemHandler $itemHandler,
        CanHitHandler $canHitHandler,
    ) {
        parent::__construct($characterAttackBuilder, $entrancingChanceHandler, $attackExtraActionHandler, $castHandler, $itemHandler, $canHitHandler);
    }

    public function doAttack($attacker, $defender, string $attackType) {
        $this->attackAndThenCast($attacker, $defender, $attackType);
    }
}
