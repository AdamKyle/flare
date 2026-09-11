<?php

namespace Tests\Traits;

use App\Flare\Models\GameLocationGemProgression;

trait CreateGameLocationGemProgression
{
    /**
     * Create a GameLocationGemProgression for tests.
     */
    public function createGameLocationGemProgression(array $options = []): GameLocationGemProgression
    {
        return GameLocationGemProgression::factory()->create($options);
    }
}
