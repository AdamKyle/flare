<?php

namespace Tests\Unit\Flare\Values;

use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\ItemUsabilityType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Values\NpcCommandTypes;
use App\Flare\Values\NpcTypes;
use App\Flare\Values\RandomAffixDetails;
use Tests\TestCase;

class RandomAffixDetailsTest extends TestCase {

    public function testThrowError() {

        $this->expectException(\Exception::class);

        new RandomAffixDetails(67);
    }

    public function testPaidTenBillion() {
        $this->assertTrue((new RandomAffixDetails(RandomAffixDetails::BASIC))->paidTenBillion());
    }

    public function testPaidFiftyBillion() {
        $this->assertTrue((new RandomAffixDetails(RandomAffixDetails::MEDIUM))->paidFiftyBillion());
    }

    public function testPaidHundredBillion() {
        $this->assertTrue((new RandomAffixDetails(RandomAffixDetails::LEGENDARY))->paidHundredBillion());
    }

}
