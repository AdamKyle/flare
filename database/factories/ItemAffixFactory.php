<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Flare\Models\ItemAffix;

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
            'name'                 => 'Sample',
            'base_damage_mod'      => 0.1,
            'type'                 => 'suffix',
            'description'          => 'Test',
            'base_healing_mod'     => 0.1,
            'str_mod'              => null,
            'dur_mod'              => null,
            'dex_mod'              => null,
            'chr_mod'              => null,
            'int_mod'              => null,
            'skill_name'           => null,
            'skill_training_bonus' => null,
            'cost'                 => 500,
        ];
    }
}
