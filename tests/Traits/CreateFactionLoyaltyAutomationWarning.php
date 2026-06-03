<?php

namespace Tests\Traits;

use App\Flare\Models\FactionLoyaltyAutomationWarning;

trait CreateFactionLoyaltyAutomationWarning
{
    public function createFactionLoyaltyAutomationWarning(array $options = []): FactionLoyaltyAutomationWarning
    {
        return FactionLoyaltyAutomationWarning::factory()->create($options);
    }
}
