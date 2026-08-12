<?php

namespace Tests\Traits;

use App\Flare\Models\ItemSkillProgression;

trait CreateItemSkillProgression
{
    public function createItemSkillProgression(array $options = []): ItemSkillProgression
    {
        return ItemSkillProgression::create(array_merge([
            'current_level' => 0,
            'current_kill' => 0,
            'is_training' => false,
        ], $options));
    }
}
