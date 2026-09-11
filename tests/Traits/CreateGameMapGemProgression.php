<?php

namespace Tests\Traits;

use App\Flare\Models\GameMapGemProgression;

trait CreateGameMapGemProgression
{
    /**
     * Create a GameMapGemProgression for tests.
     */
    public function createGameMapGemProgression(array $options = []): GameMapGemProgression
    {
        return GameMapGemProgression::factory()->create($options);
    }
}
