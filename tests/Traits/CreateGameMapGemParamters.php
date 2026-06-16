<?php

namespace Tests\Traits;

use App\Flare\Models\GameMapGemParamters;

trait CreateGameMapGemParamters
{
    public function createGameMapGemParamters(array $options = []): GameMapGemParamters
    {
        return GameMapGemParamters::factory()->create($options);
    }
}
