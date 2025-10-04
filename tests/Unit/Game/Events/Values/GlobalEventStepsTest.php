<?php

namespace Tests\Unit\Game\Events\Values;

use App\Game\Events\Values\GlobalEventSteps;
use Exception;
use Tests\TestCase;

class GlobalEventStepsTest extends TestCase
{
    public function test_invalid_event_type()
    {
        $this->expectException(Exception::class);

        new GlobalEventSteps(905);
    }

    public function test_is_battle()
    {
        $this->assertTrue((new GlobalEventSteps(GlobalEventSteps::BATTLE))->isBattle());
    }

    public function test_is_craft()
    {
        $this->assertTrue((new GlobalEventSteps(GlobalEventSteps::CRAFT))->isCrafting());
    }

    public function test_is_enchanting()
    {
        $this->assertTrue((new GlobalEventSteps(GlobalEventSteps::ENCHANT))->isEnchanting());
    }
}
