<?php

namespace Tests\Traits;

use App\Flare\Models\CharacterGemModifier;

trait CreateCharacterGemModifier
{
    public function createCharacterGemModifier(array $options = []): CharacterGemModifier
    {
        return CharacterGemModifier::factory()->create($options);
    }
}
