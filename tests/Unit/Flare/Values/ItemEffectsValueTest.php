<?php

namespace Tests\Unit\Flare\Values;

use App\Flare\Values\ItemEffectsValue;
use Tests\TestCase;

class ItemEffectsValueTest extends TestCase {

    public function testIsWalkOnWater() {
        $value = new ItemEffectsValue('walk-on-water');

        $this->assertTrue($value->walkOnWater());
    }

    public function testIsLabyrinth() {
        $value = new ItemEffectsValue('labyrinth');

        $this->assertTrue($value->labyrinth());
    }

    public function testThrowError() {

        $this->expectException(\Exception::class);

        new ItemEffectsValue('test');
    }

}
