<?php

namespace Tests\Traits;

use App\Flare\Models\CharacterGameLocationGemScroll;

trait CreateCharacterGameLocationGemScroll
{
    /**
     * Create a CharacterGameLocationGemScroll for tests.
     */
    public function createCharacterGameLocationGemScroll(array $options = []): CharacterGameLocationGemScroll
    {
        return CharacterGameLocationGemScroll::factory()->create($options);
    }
}
