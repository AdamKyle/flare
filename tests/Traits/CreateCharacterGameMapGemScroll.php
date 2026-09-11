<?php

namespace Tests\Traits;

use App\Flare\Models\CharacterGameMapGemScroll;

trait CreateCharacterGameMapGemScroll
{
    /**
     * Create a CharacterGameMapGemScroll for tests.
     */
    public function createCharacterGameMapGemScroll(array $options = []): CharacterGameMapGemScroll
    {
        return CharacterGameMapGemScroll::factory()->create($options);
    }
}
