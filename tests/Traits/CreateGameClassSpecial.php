<?php

namespace Tests\Traits;

use App\Flare\Models\GameClassSpecial;

trait CreateGameClassSpecial {

    public function createGameClassSpecial(array $options): GameClassSpecial {
        return GameClassSpecial::factory()->create($options);
    }
}
