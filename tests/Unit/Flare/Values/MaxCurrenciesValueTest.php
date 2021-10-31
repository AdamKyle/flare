<?php

namespace Tests\Unit\Flare\Values;

use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\ItemUsabilityType;
use App\Flare\Values\MaxCurrenciesValue;
use Tests\TestCase;

class MaxCurrenciesValueTest extends TestCase {

    public function testThrowError() {

        $this->expectException(\Exception::class);

        new MaxCurrenciesValue(67, 687);
    }

}
