<?php

namespace Tests\Traits;

use App\Flare\Models\DelveExploration;

trait CreateDelveExploration
{
    public function createDelveExploration(array $options = []): DelveExploration
    {
        return DelveExploration::factory()->create($options);
    }
}
