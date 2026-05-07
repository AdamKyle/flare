<?php

namespace Tests\Traits;

use App\Flare\Models\FactionLoyalty;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyAutomationLog;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Models\FactionLoyaltyNpcTask;

trait CreateFactionLoyaltyAutomation
{
    public function createFactionLoyaltyAutomation(array $options = []): FactionLoyaltyAutomation
    {
        return FactionLoyaltyAutomation::factory()->create($options);
    }

    public function createFactionLoyaltyAutomationLog(array $options = []): FactionLoyaltyAutomationLog
    {
        return FactionLoyaltyAutomationLog::factory()->create($options);
    }
}
