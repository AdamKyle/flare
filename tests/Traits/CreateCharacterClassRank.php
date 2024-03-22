<?php

namespace Tests\Traits;

use App\Flare\Models\CharacterClassRank;
use Database\Factories\CharacterClassRankFactory;

trait CreateCharacterClassRank {

    public function createCharacterClassRank(array $options = []): CharacterClassRank {
        return CharacterClassRank::factory()->create($options);
    }
}
