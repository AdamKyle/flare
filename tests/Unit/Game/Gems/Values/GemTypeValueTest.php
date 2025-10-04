<?php

namespace Tests\Unit\Game\Gems\Values;

use App\Game\Gems\Values\GemTypeValue;
use Exception;
use Tests\TestCase;

class GemTypeValueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_throw_error_for_invalid_gem_type_value()
    {
        $this->expectException(Exception::class);

        new GemTypeValue(105);
    }

    public function test_cannot_get_opposite_name_for_name_that_doesnt_exist()
    {
        $this->expectException(Exception::class);

        GemTypeValue::getOppsiteForHalfDamage('apples');
    }

    public function test_get_opposite_for_half_damage()
    {
        $element = GemTypeValue::getOppsiteForHalfDamage('fire');

        $this->assertEquals('Water', $element);
    }

    public function test_fails_to_get_opposite_for_double_damage()
    {
        $this->expectException(Exception::class);

        GemTypeValue::getOppsiteForDoubleDamage('apples');
    }

    public function test_get_opposite_for_double_damage()
    {
        $element = GemTypeValue::getOppsiteForDoubleDamage('fire');

        $this->assertEquals('Ice', $element);
    }

    public function test_is_fire()
    {
        $gemTypeValue = new GemTypeValue(GemTypeValue::FIRE);

        $this->assertTrue($gemTypeValue->isFire());
    }

    public function test_is_water()
    {
        $gemTypeValue = new GemTypeValue(GemTypeValue::WATER);

        $this->assertTrue($gemTypeValue->isWater());
    }

    public function test_is_ice()
    {
        $gemTypeValue = new GemTypeValue(GemTypeValue::ICE);

        $this->assertTrue($gemTypeValue->isIce());
    }
}
