<?php

namespace Tests\Traits;

use App\Flare\Models\DelveExploration;
use App\Flare\Models\DelveLog;
use App\Flare\Models\FactionLoyalty;
use App\Flare\Models\FactionLoyaltyNpc;

trait CreateDelveAutomation
{
    public function createDelveAutomation(array $options = []): DelveExploration
    {
        return DelveExploration::factory()->create($options);
    }

    public function createDelveAutomationLog(array $options = []): DelveLog
    {
        return DelveLog::factory()->create($options);
    }
}
