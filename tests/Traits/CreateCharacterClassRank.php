<?php

namespace Tests\Traits;

use App\Flare\Models\CharacterClassRank;

trait CreateCharacterClassRank
{
    public function createCharacterClassRank(array $options = []): CharacterClassRank
    {
        return CharacterClassRank::factory()->create($options);
    }
}
