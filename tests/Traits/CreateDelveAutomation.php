<?php

namespace Tests\Traits;

use App\Flare\Models\DelveExploration;
use App\Flare\Models\DelveLog;

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
