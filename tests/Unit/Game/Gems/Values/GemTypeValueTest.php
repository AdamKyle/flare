<?php

namespace Tests\Unit\Game\Gems\Services;

use App\Game\Gems\Values\GemTypeValue;
use Exception;
use Tests\TestCase;

class GemTypeValueTest extends TestCase {

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function testThrowErrorForInvalidGemTypeValue() {
        $this->expectException(Exception::class);

        new GemTypeValue(105);
    }

    public function testCannotGetOppositeNameForNameThatDoesntExist() {
        $this->expectException(Exception::class);

        GemTypeValue::getOppsiteForHalfDamage('apples');
    }

    public function testGetOppositeForHalfDamage() {
        $element = GemTypeValue::getOppsiteForHalfDamage('fire');

        $this->assertEquals('Water', $element);
    }

    public function testFailsToGetOppositeForDoubleDamage() {
        $this->expectException(Exception::class);

        GemTypeValue::getOppsiteForDoubleDamage('apples');
    }

    public function testGetOppositeForDoubleDamage() {
        $element = GemTypeValue::getOppsiteForDoubleDamage('fire');

        $this->assertEquals('Ice', $element);
    }

    public function testIsFire() {
        $gemTypeValue = new GemTypeValue(GemTypeValue::FIRE);

        $this->assertTrue($gemTypeValue->isFire());
    }


    public function testIsWater() {
        $gemTypeValue = new GemTypeValue(GemTypeValue::WATER);

        $this->assertTrue($gemTypeValue->isWater());
    }


    public function testIsIce() {
        $gemTypeValue = new GemTypeValue(GemTypeValue::ICE);

        $this->assertTrue($gemTypeValue->isIce());
    }
}
