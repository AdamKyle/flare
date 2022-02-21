<?php

namespace App\Flare\Handlers\AttackHandlers;

use Cache;
use App\Flare\Handlers\WeaponAndMagicAttackBase;
use App\Flare\Builders\Character\AttackDetails\CharacterAttackBuilder;
use App\Flare\Handlers\AttackExtraActionHandler;

class CastAndAttackHandler extends WeaponAndMagicAttackBase {

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
        $this->castAndThenAttack($attacker, $defender, $attackType);
    }
}
