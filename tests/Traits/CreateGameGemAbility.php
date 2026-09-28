<?php

namespace Tests\Traits;

use App\Flare\Models\GameGemAbility;

trait CreateGameGemAbility
{
    public function createGameGemAbility(array $options = []): GameGemAbility
    {
        return GameGemAbility::factory()->create($options);
    }
}
