<?php

namespace Database\Factories;

use App\Flare\Models\ItemAffix;
use App\Flare\Values\ItemAffixType;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemAffixFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ItemAffix::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => 'Sample',
            'base_damage_mod' => 0.1,
            'base_ac_mod' => 0,
            'type' => 'prefix',
            'description' => 'Test',
            'base_healing_mod' => 0.1,
            'str_mod' => null,
            'dur_mod' => null,
            'dex_mod' => null,
            'chr_mod' => null,
            'int_mod' => null,
            'skill_name' => null,
            'skill_training_bonus' => null,
            'cost' => 500,
            'int_required' => 1,
            'skill_level_required' => 1,
            'skill_level_trivial' => 10,
            'irresistible_damage' => false,
            'damage_can_stack' => false,
            'affix_type' => ItemAffixType::ACCURACY,
        ];
    }
}
