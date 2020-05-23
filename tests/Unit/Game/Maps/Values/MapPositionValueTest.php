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
            736, 1938, 1538, 1138, 738, 32
        ];

        $expected = [
            0, -1245, -1200, -800, -400, 0
        ];

        for ($i = 0; $i < count($values); $i++) {
            $position = $this->mapPositionValue->fetchXPosition($values[$i], 0);
            $this->assertEquals($expected[$i], $position);
        }
    }

    public function testMapPositionsForCharacterY() {
        $values = [
            336, 639, 640, 927, 944, 1247, 1248, 1551, 1552, 1855, 1856, 333
        ];

        $expected = [
            -304, -304, -608, -608, -900, -900, -1212, -1212, -1520, -1520, -1648, 0
        ];

        for ($i = 0; $i < count($values); $i++) {
            $position = $this->mapPositionValue->fetchYPosition($values[$i]);
            $this->assertEquals($expected[$i], $position);
        }
    }
}
