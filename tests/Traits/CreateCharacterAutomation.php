<?php

namespace Tests\Traits;

use App\Flare\Models\Adventure;
use App\Flare\Models\AdventureFloorDescriptions;
use App\Flare\Models\AdventureLog;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\Monster;
use App\Flare\Values\AutomationType;
use Database\Factories\AdventureFloorDescriptionFactory;
use Illuminate\Support\Str;

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
