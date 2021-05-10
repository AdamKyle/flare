<?php

namespace Tests\Unit\Game\Battle\Values;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Game\Battle\Values\MaxLevel;
use Tests\TestCase;

class MaxLevelTest extends TestCase {
    use RefreshDatabase;

    public function testGetFullAmount() {
        $this->assertEquals(100, (new MaxLevel(1, 100))->fetchXP());
    }

    public function testGetSeventyFivePercent() {
        $this->assertEquals(75, (new MaxLevel(500, 100))->fetchXP());
    }

    public function testGetFiftyPercent() {
        $this->assertEquals(50, (new MaxLevel(750, 100))->fetchXP());
    }

    public function testGetTwentyFivePercent() {
        $this->assertEquals(25, (new MaxLevel(950, 100))->fetchXP());
    }
}
