<?php

namespace Tests\Unit\Flare\Values;

use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\ItemUsabilityType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Values\NpcCommandTypes;
use App\Flare\Values\NpcTypes;
use Tests\TestCase;

class NpcCommandTypesTest extends TestCase {

    public function testThrowError() {

        $this->expectException(\Exception::class);

        new NpcCommandTypes(67);
    }

    public function testIsTakeKingdom() {
        $this->assertTrue((new NpcCommandTypes(NpcCommandTypes::TAKE_KINGDOM))->isTakeKingdom());
    }

    public function testIsConjure() {
        $this->assertTrue((new NpcCommandTypes(NpcCommandTypes::CONJURE))->isConjure());
    }

    public function testIsReRoll() {
        $this->assertTrue((new NpcCommandTypes(NpcCommandTypes::RE_ROLL))->isReRoll());
    }

}
