<?php

namespace Tests\Unit\Game\Battle\Values;

use App\Game\Battle\Values\CelestialConjureType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CelestialConjureTypeValueTest extends TestCase {
    use RefreshDatabase;

    public function testIsPublic() {
        $this->assertTrue((new CelestialConjureType(0))->isPublic());
    }

    public function testIsPrivate() {
        $this->assertTrue((new CelestialConjureType(1))->isPrivate());
    }

    public function testFailToDetermineType() {
        $this->expectException(\Exception::class);

        new CelestialConjureType(23);
    }

}
