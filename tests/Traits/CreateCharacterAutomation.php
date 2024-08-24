<?php

namespace Tests\Traits;

use App\Flare\Models\CharacterAutomation;
use App\Flare\Values\AutomationType;

trait CreateCharacterAutomation
{
    public function createExploringAutomation(array $details): CharacterAutomation
    {
        return CharacterAutomation::factory()->create(array_merge($details, [
            'type' => AutomationType::EXPLORING,
        ]));
    }
}
