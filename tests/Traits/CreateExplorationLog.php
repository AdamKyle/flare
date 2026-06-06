<?php

namespace Tests\Traits;

use App\Flare\Models\ExplorationLog;

trait CreateExplorationLog
{
    public function createExplorationLog(array $options = []): ExplorationLog
    {
        return ExplorationLog::factory()->create($options);
    }
}
