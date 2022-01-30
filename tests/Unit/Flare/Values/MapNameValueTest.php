<?php

namespace Tests\Unit\Flare\Values;

use App\Flare\Values\LocationEffectValue;
use App\Flare\Values\MapNameValue;
use Tests\TestCase;


class MapNameValueTest extends TestCase {

    public function testThrowError() {

        $this->expectException(\Exception::class);

        new MapNameValue('Apples');
    }

    public function testIsSurface() {
        $this->assertTrue((new MapNameValue(MapNameValue::SURFACE))->isSurface());
    }
}
