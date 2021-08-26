<?php

namespace Tests\Unit\Flare\Values;

use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\ItemUsabilityType;
use Tests\TestCase;

class ItemUsabilityTest extends TestCase {

    public function testStatIncrease() {
        $value = new ItemUsabilityType(0);

        $this->assertTrue($value->isStatIncrease());
    }

    public function testEffectsSkill() {
        $value = new ItemUsabilityType(1);

        $this->assertTrue($value->effectsSkill());
    }

    public function testDamagesKingdom() {
        $value = new ItemUsabilityType(2);

        $this->assertTrue($value->damagesKingdom());
    }

    public function testGetNamedValue() {
        $value = new ItemUsabilityType(0);

        $this->assertEquals('Stat increase', $value->getNamedValue());
    }

    public function testThrowError() {

        $this->expectException(\Exception::class);

        new ItemUsabilityType(67);
    }

}
