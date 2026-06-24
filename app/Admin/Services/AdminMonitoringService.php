<?php

namespace App\Admin\Services;

use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Values\AutomationType;

class AdminMonitoringService
{
    public function activeExplorationCount(): int
    {
        return CharacterAutomation::where('type', AutomationType::EXPLORING)
            ->where('completed_at', '>', now())
            ->count();
    }

    public function activeFactionLoyaltyCount(): int
    {
        return FactionLoyaltyAutomation::whereNull('completed_at')
            ->count();
    }

    public function activeDelveCount(): int
    {
        return CharacterAutomation::where('type', AutomationType::DELVE)
            ->where('completed_at', '>', now())
            ->count();
    }
}
