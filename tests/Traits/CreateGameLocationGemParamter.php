<?php

namespace Tests\Traits;

use App\Flare\Models\GameLocationGemParamter;

trait CreateGameLocationGemParamter
{
    public function createGameLocationGemParamter(array $options = []): GameLocationGemParamter
    {
        return GameLocationGemParamter::factory()->create($options);
    }
}
