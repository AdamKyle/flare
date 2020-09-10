<?php

namespace Tests\Unit\Game\Maps\Values;

use App\Game\Maps\Values\MapPositionValue;
use Tests\TestCase;

class MapPositionValueTest extends TestCase
{
    private $mapPositionValue = null;

    public function setUp(): void {
        parent::setUp();

        $this->mapPositionValue = resolve(MapPositionValue::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->mapPositionValue = null;
    }

    public function testMapPositionsForCharacterX() {
        $values = [
            463, 464, 465
        ];

        $expected = [
            0, 0, -150
        ];

        for ($i = 0; $i < count($values); $i++) {
            $position = $this->mapPositionValue->fetchXPosition($values[$i], 0);
            $this->assertEquals($expected[$i], $position);
        }
    }

    public function testMapPositionsForCharacterY() {
        $values = [
            319, 465
        ];

        $expected = [
            0, -150
        ];

        for ($i = 0; $i < count($values); $i++) {
            $position = $this->mapPositionValue->fetchYPosition($values[$i]);
            $this->assertEquals($expected[$i], $position);
        }
    }
}
