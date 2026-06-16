<?php

namespace Tests\Traits;

use App\Flare\Models\GameLocationGemParamters;

trait CreateGameLocationGemParamters
{
    public function createGameLocationGemParamters(array $options = []): GameLocationGemParamters
    {
        return GameLocationGemParamters::factory()->create($options);
    }
}
