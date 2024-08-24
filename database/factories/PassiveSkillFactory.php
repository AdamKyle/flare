<?php

namespace Database\Factories;

use App\Flare\Models\PassiveSkill;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Illuminate\Database\Eloquent\Factories\Factory;

class PassiveSkillFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PassiveSkill::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => 'Kingdom Management',
            'description' => 'Sample',
            'max_level' => 5,
            'hours_per_level' => 1,
            'bonus_per_level' => 0.05,
            'effect_type' => PassiveSkillTypeValue::KINGDOM_DEFENCE,
            'parent_skill_id' => null,
            'unlocks_at_level' => null,
            'is_locked' => false,
            'is_parent' => true,
        ];
    }
}
