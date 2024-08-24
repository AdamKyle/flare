<?php

namespace Tests\Unit\Game\Raids\Values;

use App\Game\Raids\Values\RaidAttackTypes;
use Exception;
use Tests\TestCase;

class RaidAttackTypeTest extends TestCase
{
    public function testThrowsExceptionForInvalidType()
    {

        $this->expectException(Exception::class);

        new RaidAttackTypes(45);
    }

    public function testIsFireAttack()
    {
        $this->assertTrue((new RaidAttackTypes(RaidAttackTypes::FIRE_ATTACK))->isFireAttack());
    }

    public function testIsIceAttack()
    {
        $this->assertTrue((new RaidAttackTypes(RaidAttackTypes::ICE_ATTACK))->isIceAttack());
    }

    public function testIsWaterAttack()
    {
        $this->assertTrue((new RaidAttackTypes(RaidAttackTypes::WATER_ATTACK))->isWaterAttack());
    }
}
