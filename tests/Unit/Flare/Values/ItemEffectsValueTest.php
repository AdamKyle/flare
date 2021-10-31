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

    public function testIsDungeon() {
        $value = new ItemEffectsValue('dungeon');

        $this->assertTrue($value->dungeon());
    }

    public function testIsWalkOnDeathWater() {
        $value = new ItemEffectsValue('walk-on-death-water');

        $this->assertTrue($value->walkOnDeathWater());
    }

    public function testThrowError() {

        $this->expectException(\Exception::class);

        new ItemEffectsValue('test');
    }

}
