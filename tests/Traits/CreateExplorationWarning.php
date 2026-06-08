<?php

namespace Tests\Traits;

use App\Flare\Models\ExplorationWarning;

trait CreateExplorationWarning
{
    public function createExplorationWarning(array $options = []): ExplorationWarning
    {
        return ExplorationWarning::factory()->create($options);
    }
}
