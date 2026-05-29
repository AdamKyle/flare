<?php

namespace Database\Factories;

use App\Flare\Models\CharacterPassiveSkill;
use Illuminate\Database\Eloquent\Factories\Factory;

class CharacterPassiveSkillFactory extends Factory
{
    protected $model = CharacterPassiveSkill::class;

    public function definition()
    {
        return [
            'character_id' => null,
            'passive_skill_id' => null,
            'parent_skill_id' => null,
            'current_level' => 0,
            'hours_to_next' => 1,
            'started_at' => null,
            'completed_at' => null,
            'is_locked' => false,
        ];
    }
}
