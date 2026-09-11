<?php

namespace Tests\Traits;

use App\Flare\Models\CharacterGameLocationGemProgression;

trait CreateCharacterGameLocationGemProgression
{
    /**
     * Create a CharacterGameLocationGemProgression for tests.
     */
    public function createCharacterGameLocationGemProgression(array $options = []): CharacterGameLocationGemProgression
    {
        return CharacterGameLocationGemProgression::factory()->create($options);
    }
}
