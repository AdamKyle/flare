<?php

namespace Tests\Traits;

use App\Flare\Models\CharacterGameMapGemProgression;

trait CreateCharacterGameMapGemProgression
{
    /**
     * Create a CharacterGameMapGemProgression for tests.
     */
    public function createCharacterGameMapGemProgression(array $options = []): CharacterGameMapGemProgression
    {
        return CharacterGameMapGemProgression::factory()->create($options);
    }
}
