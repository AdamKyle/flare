<?php

namespace Tests\Traits;

use App\Flare\Models\CharacterAutomation;
use App\Flare\Values\AutomationType;

trait CreateCharacterAutomation {

    /**
     * @param array $details
     * @return CharacterAutomation
     */
    public function createExploringAutomation(array $details): CharacterAutomation {
        return CharacterAutomation::factory()->create(array_merge($details, [
            'type' => AutomationType::EXPLORING
        ]));
    }
}
