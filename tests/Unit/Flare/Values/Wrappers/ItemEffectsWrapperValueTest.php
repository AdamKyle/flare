<?php

namespace Tests\Unit\Flare\Values\Wrappers;

use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\Wrappers\ItemEffectsHelper;
use Tests\TestCase;

class ItemEffectsWrapperValueTest extends TestCase {

    public function testInstanceOfItemEffectsForWrapper() {
        $this->assertTrue(ItemEffectsHelper::effects('labyrinth') instanceof ItemEffectsValue);
    }

}
