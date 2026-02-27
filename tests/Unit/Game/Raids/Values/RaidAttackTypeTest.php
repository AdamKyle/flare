<?php

namespace Tests\Unit\Game\Raids\Values;

use App\Game\Raids\Values\RaidAttackTypes;
use Exception;
use Tests\TestCase;

class RaidAttackTypeTest extends TestCase
{
    public function test_throws_exception_for_invalid_type()
    {

        $this->expectException(Exception::class);

        new RaidAttackTypes(45);
    }

    public function test_is_fire_attack()
    {
        $this->assertTrue((new RaidAttackTypes(RaidAttackTypes::FIRE_ATTACK))->isFireAttack());
    }

    public function test_is_ice_attack()
    {
        $this->assertTrue((new RaidAttackTypes(RaidAttackTypes::ICE_ATTACK))->isIceAttack());
    }

    public function test_is_water_attack()
    {
        $this->assertTrue((new RaidAttackTypes(RaidAttackTypes::WATER_ATTACK))->isWaterAttack());
    }

    public function testIsEnragedHate()
    {
        $this->assertTrue((new RaidAttackTypes(RaidAttackTypes::ENRAGED_HATE))->isEnragedHate());
    }
}
