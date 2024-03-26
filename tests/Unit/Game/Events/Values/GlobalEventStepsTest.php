<?php

namespace Tests\Unit\Game\Events\Values;

use Exception;
use Tests\TestCase;
use App\Game\Events\Values\GlobalEventSteps;

class GlobalEventStepsTest extends TestCase {

    public function testInvalidEventType() {
        $this->expectException(Exception::class);

        new GlobalEventSteps(905);
    }

    public function testIsBattle() {
        $this->assertTrue((new GlobalEventSteps(GlobalEventSteps::BATTLE))->isBattle());
    }

    public function testIsCraft() {
        $this->assertTrue((new GlobalEventSteps(GlobalEventSteps::CRAFT))->isCrafting());
    }

    public function testIsEnchanting() {
        $this->assertTrue((new GlobalEventSteps(GlobalEventSteps::ENCHANT))->isEnchanting());
    }
}
