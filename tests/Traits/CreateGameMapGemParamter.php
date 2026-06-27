<?php

namespace Tests\Traits;

use App\Flare\Models\GameMapGemParamter;

trait CreateGameMapGemParamter
{
    public function createGameMapGemParamter(array $options = []): GameMapGemParamter
    {
        return GameMapGemParamter::factory()->create($options);
    }
}
