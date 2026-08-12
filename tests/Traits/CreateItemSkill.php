<?php

namespace Tests\Traits;

use App\Flare\Models\ItemSkill;

trait CreateItemSkill
{
    public function createItemSkill(array $options = []): ItemSkill
    {
        return ItemSkill::create(array_merge([
            'name' => 'Sample Item Skill',
            'description' => 'Test',
            'str_mod' => 0.01,
            'max_level' => 100,
            'total_kills_needed' => 1000,
        ], $options));
    }
}
